<?php

function garantirEstruturaFinanceira(PDO $db): void {
    garantirColuna($db, 'custos_fixos', 'dia_vencimento', "INT NOT NULL DEFAULT 5");
    garantirColuna($db, 'custos_fixos', 'forma_pagamento', "VARCHAR(50) NULL DEFAULT 'pix'");
    garantirColuna($db, 'lancamentos', 'forma_pagamento', "VARCHAR(50) NULL");
    garantirColuna($db, 'lancamentos', 'custo_fixo_id', "VARCHAR(32) NULL");

    $categoria = colunaInfo($db, 'custos_fixos', 'categoria');
    if ($categoria && substr(strtolower($categoria['Type']), 0, 5) === 'enum(') {
        $db->exec("ALTER TABLE custos_fixos MODIFY categoria VARCHAR(100) NOT NULL DEFAULT 'outros'");
    }
}

function garantirColuna(PDO $db, string $tabela, string $coluna, string $definicao): void {
    if (colunaInfo($db, $tabela, $coluna)) return;
    $db->exec("ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$definicao}");
}

function colunaInfo(PDO $db, string $tabela, string $coluna): ?array {
    $tabelasPermitidas = ['custos_fixos', 'lancamentos'];
    if (!in_array($tabela, $tabelasPermitidas, true)) return null;

    $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
    $stmt->execute([$coluna]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function sincronizarLancamentosCustosFixos(PDO $db, int $meses = 12): void {
    garantirEstruturaFinanceira($db);

    $custos = $db->query("
        SELECT id, nome, valor, categoria, recorrencia, dia_vencimento, forma_pagamento
        FROM custos_fixos
        WHERE ativo = 1
        ORDER BY nome
    ")->fetchAll();

    foreach ($custos as $custo) {
        gerarLancamentosParaCustoFixo($db, $custo, $meses);
    }
}

function gerarLancamentosParaCustoFixo(PDO $db, array $custo, int $meses = 12): void {
    $dia = max(1, min(28, (int)($custo['dia_vencimento'] ?? 5)));
    $hoje = new DateTime('today');
    $base = new DateTime('first day of this month');

    for ($i = 0; $i < $meses; $i++) {
        $venc = clone $base;
        $venc->modify("+{$i} months");
        $venc->setDate((int)$venc->format('Y'), (int)$venc->format('m'), $dia);

        if ($venc < $hoje) continue;
        if (($custo['recorrencia'] ?? 'mensal') === 'anual' && $i > 0 && $venc->format('m') !== $base->format('m')) continue;
        if (existeLancamentoCustoFixoNoMes($db, $custo['id'], $venc)) continue;

        $stmt = $db->prepare('
            INSERT INTO lancamentos
                (id,tipo,descricao,valor,valor_pago,categoria,vencimento,status,modalidade,forma_pagamento,custo_fixo_id)
            VALUES
                (?,\'pagar\',?,?,0,?,?,\'pendente\',\'avista\',?,?)
        ');
        $stmt->execute([
            gerarId(),
            $custo['nome'],
            $custo['valor'],
            $custo['categoria'] ?? 'outros',
            $venc->format('Y-m-d'),
            $custo['forma_pagamento'] ?? 'pix',
            $custo['id'],
        ]);
    }
}

function existeLancamentoCustoFixoNoMes(PDO $db, string $custoId, DateTime $venc): bool {
    $stmt = $db->prepare("
        SELECT id
        FROM lancamentos
        WHERE custo_fixo_id = ?
          AND DATE_FORMAT(vencimento, '%Y-%m') = ?
          AND status != 'cancelado'
        LIMIT 1
    ");
    $stmt->execute([$custoId, $venc->format('Y-m')]);
    return (bool)$stmt->fetch();
}
