<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

$db = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];
garantirEstruturaFinanceira($db);

switch ($metodo) {
    case 'GET':
        sincronizarLancamentosCustosFixos($db);
        $rows = $db->query('SELECT * FROM custos_fixos ORDER BY categoria, nome')->fetchAll();
        responderJson($rows);

    case 'POST':
        $d = lerCorpo();
        validarCustoFixo($d);

        $id = gerarId();
        $stmt = $db->prepare('
            INSERT INTO custos_fixos
                (id,nome,valor,categoria,recorrencia,dia_vencimento,forma_pagamento,ativo)
            VALUES
                (?,?,?,?,?,?,?,1)
        ');
        $stmt->execute([
            $id,
            $d['nome'],
            $d['valor'],
            $d['categoria'] ?? 'outros',
            $d['recorrencia'] ?? 'mensal',
            $d['dia_vencimento'] ?? 5,
            $d['forma_pagamento'] ?? 'pix',
        ]);

        $d['id'] = $id;
        gerarLancamentosParaCustoFixo($db, $d);
        responderJson(['ok' => true, 'id' => $id], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatorio'], 422);
        validarCustoFixo($d);

        $stmt = $db->prepare('
            UPDATE custos_fixos
            SET nome=?, valor=?, categoria=?, recorrencia=?, dia_vencimento=?, forma_pagamento=?, ativo=?
            WHERE id=?
        ');
        $stmt->execute([
            $d['nome'],
            $d['valor'],
            $d['categoria'] ?? 'outros',
            $d['recorrencia'] ?? 'mensal',
            $d['dia_vencimento'] ?? 5,
            $d['forma_pagamento'] ?? 'pix',
            $d['ativo'] ?? 1,
            $d['id'],
        ]);

        atualizarLancamentosFuturos($db, $d['id'], $d);
        sincronizarLancamentosCustosFixos($db);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatorio'], 422);
        $db->prepare("
            DELETE FROM lancamentos
            WHERE custo_fixo_id=?
              AND status IN ('pendente','atrasado')
              AND vencimento >= CURDATE()
        ")->execute([$id]);
        $db->prepare('DELETE FROM custos_fixos WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

    default:
        responderJson(['erro' => 'Metodo nao permitido'], 405);
}

function validarCustoFixo(array $d): void {
    if (empty($d['nome']) || empty($d['valor'])) {
        responderJson(['erro' => 'Nome e valor sao obrigatorios'], 422);
    }
}

function atualizarLancamentosFuturos(PDO $db, string $custoId, array $d): void {
    $stmt = $db->prepare("
        UPDATE lancamentos
        SET descricao=?, valor=?, categoria=?, forma_pagamento=?
        WHERE custo_fixo_id=?
          AND status IN ('pendente','atrasado')
          AND vencimento >= CURDATE()
    ");
    $stmt->execute([
        $d['nome'],
        $d['valor'],
        $d['categoria'] ?? 'outros',
        $d['forma_pagamento'] ?? 'pix',
        $custoId,
    ]);
}
