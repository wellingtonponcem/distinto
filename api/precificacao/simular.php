<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

if (!GROQ_API_KEY) {
    responderJson(['erro' => 'Groq API Key não configurada. Edite config/env.php e adicione sua GROQ_API_KEY.'], 503);
}

$briefing = lerCorpo();

// Validar campos obrigatórios
if (empty($briefing['servicos'])) {
    responderJson(['erro' => 'Selecione pelo menos um serviço'], 422);
}
if (empty($briefing['segmento'])) {
    responderJson(['erro' => 'Informe o segmento do cliente'], 422);
}

// Buscar dados do banco para contextualizar o prompt
$db = Database::get();

// Custos fixos mensais
$custos = $db->query("SELECT nome, valor, recorrencia FROM custos_fixos WHERE ativo=1")->fetchAll();
$totalCustosFixos = array_reduce($custos, function($carry, $c) {
    return $carry + ($c['recorrencia'] === 'anual' ? $c['valor'] / 12 : $c['valor']);
}, 0);

// Serviços e preços mínimos (com rateio de 160h mensais)
$servicos = $db->query("SELECT nome, horas_estimadas, custo_producao, custos_variaveis, markup FROM servicos WHERE ativo=1")->fetchAll();
$horasMensais = 160;
$linhasServicos = [];
foreach ($servicos as $s) {
    $rateio = $horasMensais > 0 ? ($s['horas_estimadas'] / $horasMensais) * $totalCustosFixos : 0;
    $custoTotal = $rateio + $s['custo_producao'] + $s['custos_variaveis'];
    $precoMin = $custoTotal * (1 + $s['markup'] / 100);
    $linhasServicos[] = "- {$s['nome']}: {$s['horas_estimadas']}h/mês, preço mínimo R$ " . number_format($precoMin, 2, ',', '.');
}

$servicosStr = implode("\n", $linhasServicos) ?: 'Nenhum serviço cadastrado ainda.';

// Mapear labels
$tipoLabels = [
    'pequena_empresa' => 'Pequena Empresa', 'media_empresa' => 'Média Empresa',
    'grande_empresa' => 'Grande Empresa', 'startup' => 'Startup',
    'profissional_liberal' => 'Profissional Liberal', 'ecommerce' => 'E-commerce',
];
$prazoLabels = [
    'urgente' => 'Urgente (menos de 2 semanas)', 'normal' => 'Normal (1 mês)',
    'longo' => 'Longo prazo (2+ meses)', 'mensal_recorrente' => 'Contrato mensal recorrente',
];
$complexLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];

$tipoCliente   = $tipoLabels[$briefing['tipo_cliente'] ?? 'media_empresa'] ?? 'Média Empresa';
$prazo         = $prazoLabels[$briefing['prazo'] ?? 'normal'] ?? 'Normal';
$complexidade  = $complexLabels[$briefing['complexidade'] ?? 'media'] ?? 'Média';
$servicosSel   = implode(', ', (array)$briefing['servicos']);
$cliente       = !empty($briefing['cliente']) ? "Cliente: {$briefing['cliente']}\n" : '';
$contexto      = !empty($briefing['contexto']) ? "\nContexto adicional: {$briefing['contexto']}" : '';

// Montar prompt
$prompt = <<<PROMPT
Você é um consultor de precificação para agências de marketing brasileiras.
Responda SEMPRE em português do Brasil com formatação clara em Markdown.

DADOS DA AGÊNCIA:
Custo fixo mensal total: R$ {$totalCustosFixos}

TABELA DE SERVIÇOS E PREÇOS MÍNIMOS (respeite estes valores):
{$servicosStr}

BRIEFING:
{$cliente}Tipo de cliente: {$tipoCliente}
Segmento/nicho: {$briefing['segmento']}
Serviços desejados: {$servicosSel}
Prazo: {$prazo}
Complexidade: {$complexidade}{$contexto}

REGRAS OBRIGATÓRIAS:
1. NUNCA sugira valores abaixo do preço mínimo de cada serviço listado
2. Apresente EXATAMENTE 2 opções: "## Opção Básica" e "## Opção Completa"
3. Use tom consultivo e profissional, não de vendedor
4. Para cada opção inclua: escopo detalhado, justificativa e investimento total
5. Os preços devem ser realistas para o mercado brasileiro

FORMATO ESPERADO:
## Opção Básica
**Escopo:** [lista detalhada]
**Justificativa:** [por que esses serviços para esse perfil]
**Investimento:** R$ X.XXX,XX/mês

---

## Opção Completa
**Escopo:** [lista detalhada]
**Justificativa:** [valor adicional em relação à opção básica]
**Investimento:** R$ X.XXX,XX/mês
PROMPT;

// Chamar Groq API via cURL
$payload = json_encode([
    'model'      => GROQ_MODEL,
    'messages'   => [['role' => 'user', 'content' => $prompt]],
    'max_tokens' => 1200,
    'temperature'=> 0.7,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . GROQ_API_KEY,
        'Content-Type: application/json',
    ],
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erro     = curl_error($ch);
curl_close($ch);

if ($erro) {
    responderJson(['erro' => 'Falha na conexão com a API: ' . $erro], 502);
}

$dados = json_decode($resposta, true);

if ($httpCode !== 200 || empty($dados['choices'][0]['message']['content'])) {
    $msgErro = $dados['error']['message'] ?? 'Erro desconhecido da API Groq';
    responderJson(['erro' => $msgErro], 502);
}

$proposta = $dados['choices'][0]['message']['content'];
responderJson(['ok' => true, 'proposta' => $proposta]);
