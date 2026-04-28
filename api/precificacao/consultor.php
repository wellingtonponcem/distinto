<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$dados = lerCorpo();
$mensagens = $dados['mensagens'] ?? [];

if (empty($mensagens)) {
    responderJson(['erro' => 'Histórico de mensagens vazio'], 400);
}

// Migração e Busca de Memória
try {
    $db = Database::get();
    $stmt = $db->query("SHOW COLUMNS FROM configuracao_empresa LIKE 'memoria_ia'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE configuracao_empresa ADD COLUMN memoria_ia LONGTEXT NULL");
    }
} catch (Exception $e) {}

$config = $db->query("SELECT memoria_ia FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$memoriaAgencia = $config['memoria_ia'] ?? "Ainda não há fatos específicos memorizados sobre equipamentos ou processos.";

$custos = $db->query("SELECT nome, valor, recorrencia FROM custos_fixos WHERE ativo=1")->fetchAll();
$totalCustosFixos = array_reduce($custos, function($carry, $c) {
    return $carry + ($c['recorrencia'] === 'anual' ? $c['valor'] / 12 : $c['valor']);
}, 0);

$custosStr = "";
foreach($custos as $c) {
    $custosStr .= "- {$c['nome']}: R$ " . number_format($c['valor'], 2, ',', '.') . " ({$c['recorrencia']})\n";
}

$systemPrompt = <<<PROMPT
Você é um Consultor de Precificação Estratégica para uma agência de serviços variados (Marketing, Audiovisual, Design, etc.).
Seu objetivo é ajudar o dono da agência a chegar no preço ideal para um serviço específico através de uma conversa consultiva.

CONTEXTO FINANCEIRO DA AGÊNCIA:
- Custo Fixo Mensal Total: R$ {$totalCustosFixos}
- Detalhes dos custos:
{$custosStr}

FATOS E RECURSOS MEMORIZADOS (Use isso para não perguntar novamente):
{$memoriaAgencia}

REGRAS DA CONVERSA:
1. Comece sendo cordial e pergunte qual serviço ele deseja precificar hoje.
2. Uma vez que ele responder o serviço, faça perguntas inteligentes para entender os custos variáveis e a complexidade:
   - Se for Audiovisual, pergunte sobre equipamentos (depreciação), diárias, locação, equipe.
   - Se for Marketing/Design, pergunte sobre horas estimadas, ferramentas específicas, nível de senioridade exigido.
   - Sempre pergunte se haverá terceirização ou custos diretos (anúncios, viagens, materiais).
3. Seja breve em cada interação. Não faça 10 perguntas de uma vez. Faça 2 ou 3 perguntas chave por vez para manter a conversa fluida.
4. Quando tiver informações suficientes, apresente um CÁLCULO SUGERIDO:
   - Baseie o preço na: (Parcela do Custo Fixo + Custos Diretos + Margem de Lucro desejada).
   - Sugira um valor final estratégico (valor cheio/arredondado).
5. Use tom profissional, consultivo e focado em lucratividade. Responda em Português do Brasil e use Markdown.

MEMORIZAÇÃO AUTOMÁTICA:
Se você identificar um fato NOVO e PERMANENTE sobre a agência (equipamentos, diárias, processos, ferramentas), você deve incluí-lo ao final da sua resposta dentro de uma tag <memory>fatos aqui...</memory>. 
Não repita fatos que já estão no contexto de MEMÓRIA ATUAL acima. Se não houver nada novo, não use a tag.
PROMPT;

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $mensagens
    ),
    'temperature' => 0.7,
    'max_tokens' => 1000
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    responderJson(['erro' => 'Erro na API da IA'], 502);
}

$dadosIa = json_decode($resposta, true);
$textoIa = $dadosIa['choices'][0]['message']['content'] ?? 'Desculpe, tive um problema ao processar sua resposta.';

// Processar Memória Automática
if (preg_match('/<memory>(.*?)<\/memory>/s', $textoIa, $matches)) {
    $novosFatos = trim($matches[1]);
    $textoIa = str_replace($matches[0], '', $textoIa); // Limpar da resposta do usuário
    
    // Concatenar com a memória existente de forma inteligente
    $memoriaCombinada = $memoriaAgencia . "\n" . $novosFatos;
    
    $stmt = $db->prepare("UPDATE configuracao_empresa SET memoria_ia = ? WHERE id = 'principal'");
    $stmt->execute([$memoriaCombinada]);
    $memoriaAgencia = $memoriaCombinada;
}

responderJson([
    'resposta' => trim($textoIa),
    'memoria' => $memoriaAgencia
]);
