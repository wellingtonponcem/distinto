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
        $d = lerCorpo();
        $id = gerarId();
        $stmt = $db->prepare('INSERT INTO custos_fixos (id, nome, valor, categoria, recorrencia, ativo) VALUES (?,?,?,?,?,1)');
        $stmt->execute([$id, $d['nome'], $d['valor'], $d['categoria'], $d['recorrencia']]);
        responderJson(['ok' => true, 'id' => $id], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        $stmt = $db->prepare('UPDATE custos_fixos SET nome=?, valor=?, categoria=?, recorrencia=?, ativo=? WHERE id=?');
        $stmt->execute([$d['nome'], $d['valor'], $d['categoria'], $d['recorrencia'], $d['ativo'] ?? 1, $d['id']]);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('DELETE FROM custos_fixos WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}
