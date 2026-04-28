<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$dados = lerCorpo();
$mensagens = $dados['mensagens'] ?? [];

if (empty($mensagens)) responderJson(['erro' => 'Sem mensagens'], 400);

$db = Database::get();
$config = $db->query("SELECT memoria_ia FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$memoriaAtual = $config['memoria_ia'] ?? "";

$historicoStr = "";
foreach($mensagens as $m) {
    $historicoStr .= "{$m['role']}: {$m['content']}\n";
}

$prompt = <<<PROMPT
Você é um extrator de fatos operacionais.
Abaixo está uma conversa entre um consultor de precificação e um dono de agência.
Sua tarefa é extrair FATOS PERMANENTES que devem ser lembrados para futuras precificações.

MEMÓRIA ATUAL:
{$memoriaAtual}

CONVERSA RECENTE:
{$historicoStr}

INSTRUÇÕES:
1. Identifique novos equipamentos, ferramentas, valores de diárias, nomes de parceiros frequentes, ou processos padrão citados pelo usuário.
2. Atualize a MEMÓRIA ATUAL incluindo esses novos fatos de forma organizada.
3. Mantenha o que já era conhecido, apenas adicione ou corrija se houver informação nova.
4. Responda APENAS com o novo bloco de texto da Memória Atualizada, sem comentários.
5. Se não houver nada de útil para memorizar, repita a MEMÓRIA ATUAL.
PROMPT;

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.1
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . GROQ_API_KEY,
        'Content-Type: application/json'
    ]
]);

$resposta = curl_exec($ch);

$dadosIa = json_decode($resposta, true);
$novaMemoria = $dadosIa['choices'][0]['message']['content'] ?? $memoriaAtual;

// Salvar no banco
$stmt = $db->prepare("UPDATE configuracao_empresa SET memoria_ia = ? WHERE id = 'principal'");
$stmt->execute([$novaMemoria]);

responderJson(['ok' => true, 'memoria' => $novaMemoria]);
