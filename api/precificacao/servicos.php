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
        $rows = $db->query('SELECT * FROM servicos WHERE ativo=1 ORDER BY nome')->fetchAll();
        responderJson($rows);

    case 'POST':
        $d = lerCorpo();
        if (empty($d['nome'])) responderJson(['erro' => 'Nome obrigatório'], 422);
        $id = gerarId();
        $stmt = $db->prepare('INSERT INTO servicos (id, nome, descricao, horas_estimadas, custo_producao, custos_variaveis, markup) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$id, $d['nome'], $d['descricao'] ?? null, $d['horas_estimadas'] ?? 0, $d['custo_producao'] ?? 0, $d['custos_variaveis'] ?? 0, $d['markup'] ?? 30]);
        responderJson(['ok' => true, 'id' => $id], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        $stmt = $db->prepare('UPDATE servicos SET nome=?, descricao=?, horas_estimadas=?, custo_producao=?, custos_variaveis=?, markup=? WHERE id=?');
        $stmt->execute([$d['nome'], $d['descricao'] ?? null, $d['horas_estimadas'] ?? 0, $d['custo_producao'] ?? 0, $d['custos_variaveis'] ?? 0, $d['markup'] ?? 30, $d['id']]);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('UPDATE servicos SET ativo=0 WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}
