<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$db     = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $rows = $db->query('SELECT * FROM custos_fixos ORDER BY categoria, nome')->fetchAll();
        responderJson($rows);

    case 'POST':
        $d  = lerCorpo();
        $id = gerarId();
        $stmt = $db->prepare('INSERT INTO custos_fixos (id,nome,valor,categoria,recorrencia,dia_vencimento,forma_pagamento,ativo) VALUES (?,?,?,?,?,?,?,1)');
        $stmt->execute([$id, $d['nome'], $d['valor'], $d['categoria'], $d['recorrencia'], $d['dia_vencimento'] ?? 5, $d['forma_pagamento'] ?? 'pix']);
        gerarLancamentosCustoFixo($db, $id, $d);
        responderJson(['ok' => true, 'id' => $id], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        $stmt = $db->prepare('UPDATE custos_fixos SET nome=?,valor=?,categoria=?,recorrencia=?,dia_vencimento=?,forma_pagamento=?,ativo=? WHERE id=?');
        $stmt->execute([$d['nome'], $d['valor'], $d['categoria'], $d['recorrencia'], $d['dia_vencimento'] ?? 5, $d['forma_pagamento'] ?? 'pix', $d['ativo'] ?? 1, $d['id']]);
        atualizarLancamentosFuturos($db, $d['id'], $d);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('DELETE FROM custos_fixos WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}

// Gera lançamentos mensais para os próximos 12 meses
function gerarLancamentosCustoFixo(PDO $db, string $custoId, array $d): void {
    $dia  = max(1, min(28, (int)($d['dia_vencimento'] ?? 5)));
    $hoje = new DateTime();

    for ($i = 0; $i < 12; $i++) {
        $venc = new DateTime();
        $venc->setDate((int)$venc->format('Y'), (int)$venc->format('m'), $dia);
        $venc->modify("+$i months");

        // Pular meses já vencidos (mês atual OK se dia ainda não passou)
        if ($i === 0 && $venc < $hoje) continue;

        $lId = gerarId();
        $stmt = $db->prepare('
            INSERT INTO lancamentos (id,tipo,descricao,valor,valor_pago,categoria,vencimento,status,modalidade,forma_pagamento,custo_fixo_id)
            VALUES (?,\'pagar\',?,?,0,?,?,\'pendente\',\'avista\',?,?)
        ');
        $stmt->execute([$lId, $d['nome'], $d['valor'], $d['categoria'], $venc->format('Y-m-d'), $d['forma_pagamento'] ?? 'pix', $custoId]);
    }
}

// Atualiza lançamentos futuros não pagos vinculados ao custo fixo
function atualizarLancamentosFuturos(PDO $db, string $custoId, array $d): void {
    $stmt = $db->prepare("
        UPDATE lancamentos
        SET nome_desc=?, valor=?, categoria=?, forma_pagamento=?
        WHERE custo_fixo_id=? AND status IN ('pendente','atrasado') AND vencimento >= CURDATE()
    ");
    // Coluna correta é descricao
    $stmt = $db->prepare("
        UPDATE lancamentos
        SET descricao=?, valor=?, categoria=?, forma_pagamento=?
        WHERE custo_fixo_id=? AND status IN ('pendente','atrasado') AND vencimento >= CURDATE()
    ");
    $stmt->execute([$d['nome'], $d['valor'], $d['categoria'], $d['forma_pagamento'] ?? 'pix', $custoId]);
}
