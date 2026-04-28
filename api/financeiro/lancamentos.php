<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

$db     = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];
garantirEstruturaFinanceira($db);

switch ($metodo) {
    case 'GET':
        sincronizarLancamentosCustosFixos($db);
        $rows = $db->query("
            SELECT *,
              CASE
                WHEN valor_pago >= valor THEN 'pago'
                WHEN valor_pago > 0 THEN 'pago_parcial'
                WHEN vencimento < CURDATE() AND status NOT IN ('pago','cancelado') THEN 'atrasado'
                ELSE status
              END AS status
            FROM lancamentos
            ORDER BY vencimento DESC
        ")->fetchAll();
        responderJson($rows);

    case 'POST':
        $d = lerCorpo();
        validarLancamento($d);

        // Se marcado como custo fixo, criar/vincular custo_fixo
        if (($d['tipo'] ?? '') === 'pagar' && !empty($d['e_custo_fixo']) && empty($d['custo_fixo_id'])) {
            $d['custo_fixo_id'] = criarCustoFixoFromLancamento($db, $d);
        }

        criarLancamento($db, $d);
        sincronizarLancamentosCustosFixos($db);
        responderJson(['ok' => true], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        validarLancamento($d);
        $stmt = $db->prepare('UPDATE lancamentos SET tipo=?,descricao=?,valor=?,categoria=?,cliente_fornecedor=?,vencimento=?,modalidade=?,forma_pagamento=?,observacao=? WHERE id=?');
        $stmt->execute([
            $d['tipo'], $d['descricao'], $d['valor'], $d['categoria'] ?? 'outros',
            $d['cliente_fornecedor'] ?? null, $d['vencimento'],
            $d['modalidade'] ?? 'avista', $d['forma_pagamento'] ?? null,
            $d['observacao'] ?? null, $d['id']
        ]);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('DELETE FROM lancamentos WHERE lancamento_pai_id=?')->execute([$id]);
        $db->prepare('DELETE FROM lancamentos WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}

function validarLancamento(array $d): void {
    if (empty($d['descricao']) || empty($d['valor']) || empty($d['vencimento'])) {
        responderJson(['erro' => 'Descrição, valor e vencimento são obrigatórios'], 422);
    }
}

function criarCustoFixoFromLancamento(PDO $db, array $d): string {
    $id  = gerarId();
    $dia = (int)date('d', strtotime($d['vencimento']));
    $dia = max(1, min(28, $dia));

    $colunas = ['id', 'nome', 'valor', 'categoria', 'recorrencia', 'ativo'];
    $valores = ['?', '?', '?', '?', "'mensal'", '1'];
    $params = [$id, $d['descricao'], $d['valor'], $d['categoria'] ?? 'outros'];

    if (tabelaTemColuna($db, 'custos_fixos', 'dia_vencimento')) {
        $colunas[] = 'dia_vencimento';
        $valores[] = '?';
        $params[] = $dia;
    }
    if (tabelaTemColuna($db, 'custos_fixos', 'forma_pagamento')) {
        $colunas[] = 'forma_pagamento';
        $valores[] = '?';
        $params[] = $d['forma_pagamento'] ?? 'pix';
    }

    $stmt = $db->prepare('INSERT INTO custos_fixos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
    $stmt->execute($params);
    return $id;
}

function criarLancamento(PDO $db, array $d): void {
    $modalidade = $d['modalidade'] ?? 'avista';

    if ($modalidade === 'parcelado') {
        $total        = max(2, min(120, (int)($d['total_parcelas'] ?? 2)));
        $valorParcela = round((float)$d['valor'] / $total, 2);
        $vencBase     = new DateTime($d['vencimento']);
        $paiId        = gerarId();

        inserirLancamento($db, $paiId, $d, $valorParcela, $vencBase->format('Y-m-d'), null, 1, $total);
        for ($i = 2; $i <= $total; $i++) {
            $vencBase->modify('+30 days');
            inserirLancamento($db, gerarId(), $d, $valorParcela, $vencBase->format('Y-m-d'), $paiId, $i, $total);
        }

    } elseif ($modalidade === 'recorrente') {
        $freq     = $d['frequencia'] ?? 'mensal';
        $termino  = !empty($d['data_termino']) ? new DateTime($d['data_termino']) : null;
        $limite   = (new DateTime())->modify('+12 months');
        $fim      = $termino && $termino < $limite ? $termino : $limite;
        $venc     = new DateTime($d['vencimento']);
        $intervalo = match($freq) { 'semanal' => 'P7D', 'anual' => 'P1Y', default => 'P1M' };
        $paiId    = null;

        while ($venc <= $fim) {
            $id = gerarId();
            inserirLancamento($db, $id, $d, $d['valor'], $venc->format('Y-m-d'), $paiId);
            if (!$paiId) $paiId = $id;
            $venc->add(new DateInterval($intervalo));
        }
    } else {
        inserirLancamento($db, gerarId(), $d, $d['valor'], $d['vencimento']);
    }
}

function inserirLancamento(PDO $db, string $id, array $d, float $valor, string $venc, ?string $paiId = null, int $parcelaAtual = 1, ?int $totalParcelas = null): void {
    $colunas = ['id','tipo','descricao','valor','valor_pago','categoria','cliente_fornecedor','vencimento','status','modalidade','total_parcelas','parcela_atual','lancamento_pai_id','frequencia','data_termino','observacao'];
    $valores = ['?','?','?','?','0','?','?','?',"'pendente'",'?','?','?','?','?','?','?'];
    $params = [
        $id, $d['tipo'], $d['descricao'], $valor,
        $d['categoria'] ?? 'outros', $d['cliente_fornecedor'] ?? null,
        $venc, $d['modalidade'] ?? 'avista',
        $totalParcelas, $parcelaAtual, $paiId,
        $d['frequencia'] ?? null, $d['data_termino'] ?? null,
        $d['observacao'] ?? null
    ];

    if (tabelaTemColuna($db, 'lancamentos', 'forma_pagamento')) {
        $colunas[] = 'forma_pagamento';
        $valores[] = '?';
        $params[] = $d['forma_pagamento'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) {
        $colunas[] = 'custo_fixo_id';
        $valores[] = '?';
        $params[] = $d['custo_fixo_id'] ?? null;
    }

    $stmt = $db->prepare('INSERT INTO lancamentos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
    $stmt->execute($params);
}
