<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhum arquivo enviado ou erro no upload']);
    exit;
}

$content = file_get_contents($_FILES['arquivo']['tmp_name']);

// Basic OFX Parser
$transactions = [];
preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);

foreach ($matches[1] as $txn) {
    preg_match('/<TRNTYPE>(.*?)(?:\r|\n|<)/', $txn, $typeMatch);
    preg_match('/<DTPOSTED>(.*?)(?:\r|\n|<)/', $txn, $dateMatch);
    preg_match('/<TRNAMT>(.*?)(?:\r|\n|<)/', $txn, $amtMatch);
    preg_match('/<FITID>(.*?)(?:\r|\n|<)/', $txn, $idMatch);
    preg_match('/<MEMO>(.*?)(?:\r|\n|<)/', $txn, $memoMatch);

    $dateStr = substr(trim($dateMatch[1] ?? ''), 0, 8);
    $date = '';
    if (strlen($dateStr) === 8) {
        $date = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
    }

    $amountRaw = (float)trim($amtMatch[1] ?? '0');
    $tipo = $amountRaw >= 0 ? 'receber' : 'pagar';
    $amount = abs($amountRaw);
    
    // Some banks use ISO-8859-1 in OFX
    $memo = trim($memoMatch[1] ?? '');
    if (!mb_check_encoding($memo, 'UTF-8')) {
        $memo = utf8_encode($memo);
    }

    if ($date && $amount > 0) {
        $transactions[] = [
            'fitid' => trim($idMatch[1] ?? uniqid()),
            'tipo' => $tipo,
            'data' => $date,
            'valor' => $amount,
            'descricao' => $memo,
        ];
    }
}

echo json_encode(['ok' => true, 'transacoes' => $transactions]);
