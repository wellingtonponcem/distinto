<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

try {
    $db = Database::get();
    echo "<h1>Conexão OK</h1>";
    
    echo "<h2>Tabelas e Colunas Cruciais</h2>";
    
    $checks = [
        ['tabela' => 'users', 'coluna' => 'nivel'],
        ['tabela' => 'lancamentos', 'coluna' => 'conta_id'],
        ['tabela' => 'servicos', 'coluna' => 'preco_venda']
    ];

    foreach ($checks as $check) {
        $t = $check['tabela'];
        $c = $check['coluna'];
        try {
            $stmt = $db->query("SHOW COLUMNS FROM $t LIKE '$c'");
            if (!$stmt->fetch()) {
                echo "<p style='color:orange'>Tabela '$t': Coluna '$c' FALTANDO. Tentando criar...</p>";
                if ($c === 'nivel') $sql = "ALTER TABLE $t ADD COLUMN $c INT NOT NULL DEFAULT 0";
                elseif ($c === 'conta_id') $sql = "ALTER TABLE $t ADD COLUMN $c VARCHAR(50) NULL";
                elseif ($c === 'preco_venda') $sql = "ALTER TABLE $t ADD COLUMN $c DECIMAL(15,2) DEFAULT 0";
                
                $db->exec($sql);
                echo "<p style='color:green'>Sucesso ao criar '$c' em '$t'!</p>";
            } else {
                echo "<p style='color:green'>Tabela '$t': Coluna '$c' OK.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>ERRO em '$t.$c': " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>Verificando tabela 'contas_bancarias'</h3>";
    try {
        $db->query("SELECT 1 FROM contas_bancarias LIMIT 1");
        echo "<p style='color:green'>Tabela 'contas_bancarias' OK.</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>Tabela 'contas_bancarias' FALTANDO. Tentando criar...</p>";
        $db->exec("CREATE TABLE IF NOT EXISTS contas_bancarias (
            id VARCHAR(50) PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            saldo_inicial DECIMAL(15,2) DEFAULT 0,
            cor VARCHAR(20) DEFAULT '#2a2a2a',
            ativo TINYINT(1) DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<p style='color:green'>Sucesso ao criar 'contas_bancarias'!</p>";
    }

} catch (Exception $e) {
    echo "<h1 style='color:red'>ERRO: " . $e->getMessage() . "</h1>";
}
