<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
exigirAutenticacao();

$tituloPagina = 'Dashboard';

// Buscar resumo financeiro
$db = Database::get();
$hoje = date('Y-m-d');
$mesInicio = date('Y-m-01');
$mesFim    = date('Y-m-t');

// Cards de resumo
$queryResumo = $db->prepare("
    SELECT
        SUM(CASE WHEN tipo='receber' AND status='pago' THEN valor_pago ELSE 0 END) as total_recebido,
        SUM(CASE WHEN tipo='pagar'   AND status='pago' THEN valor_pago ELSE 0 END) as total_pago,
        SUM(CASE WHEN tipo='receber' AND vencimento BETWEEN ? AND ? AND status != 'pago' THEN valor ELSE 0 END) as receber_mes,
        SUM(CASE WHEN tipo='pagar'   AND vencimento BETWEEN ? AND ? AND status != 'pago' THEN valor ELSE 0 END) as pagar_mes
    FROM lancamentos WHERE status != 'cancelado'
");
$queryResumo->execute([$mesInicio, $mesFim, $mesInicio, $mesFim]);
$resumo = $queryResumo->fetch();

$saldoAtual    = ($resumo['total_recebido'] ?? 0) - ($resumo['total_pago'] ?? 0);
$receberMes    = $resumo['receber_mes'] ?? 0;
$pagarMes      = $resumo['pagar_mes'] ?? 0;
$resultadoPrev = $receberMes - $pagarMes;

// Contas vencidas
$vencidas = $db->prepare("
    SELECT id, tipo, descricao, valor, valor_pago, vencimento, cliente_fornecedor
    FROM lancamentos
    WHERE vencimento < ? AND status IN ('pendente','pago_parcial')
    ORDER BY vencimento ASC LIMIT 10
");
$vencidas->execute([$hoje]);
$contasVencidas = $vencidas->fetchAll();

// Vencendo em 7 dias
$seteDias = date('Y-m-d', strtotime('+7 days'));
$proximas = $db->prepare("
    SELECT id, tipo, descricao, valor, valor_pago, vencimento, cliente_fornecedor
    FROM lancamentos
    WHERE vencimento BETWEEN ? AND ? AND status IN ('pendente','pago_parcial')
    ORDER BY vencimento ASC LIMIT 10
");
$proximas->execute([$hoje, $seteDias]);
$contasProximas = $proximas->fetchAll();

// Fluxo dos próximos 30 dias (agrupado por semana)
$fluxo30 = $db->prepare("
    SELECT DATE(vencimento) as data,
           SUM(CASE WHEN tipo='receber' THEN valor ELSE 0 END) as entradas,
           SUM(CASE WHEN tipo='pagar'   THEN valor ELSE 0 END) as saidas
    FROM lancamentos
    WHERE vencimento BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)
      AND status IN ('pendente','pago_parcial')
    GROUP BY DATE(vencimento)
    ORDER BY data ASC
");
$fluxo30->execute([$hoje, $hoje]);
$dadosFluxo = $fluxo30->fetchAll();

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <!-- Header da página -->
        <div style="margin-bottom:28px;">
            <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Dashboard</h1>
            <p style="font-size:14px; color:#6b7280; margin-top:2px;">Visão geral do fluxo financeiro</p>
        </div>

        <!-- Cards de Resumo -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:28px;">

            <div class="card stat-card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="wallet" style="width:13px;height:13px;"></i> Saldo Atual
                </div>
                <div style="font-size:26px; font-weight:700; color:<?= $saldoAtual >= 0 ? '#10b981' : '#ef4444' ?>;">
                    <?= formatarMoeda($saldoAtual) ?>
                </div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">Recebido − Pago</div>
            </div>

            <div class="card stat-card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="trending-up" style="width:13px;height:13px;"></i> A Receber no Mês
                </div>
                <div style="font-size:26px; font-weight:700; color:#10b981;"><?= formatarMoeda($receberMes) ?></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;"><?= date('F/Y') ?></div>
            </div>

            <div class="card stat-card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="trending-down" style="width:13px;height:13px;"></i> A Pagar no Mês
                </div>
                <div style="font-size:26px; font-weight:700; color:#ef4444;"><?= formatarMoeda($pagarMes) ?></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;"><?= date('F/Y') ?></div>
            </div>

            <div class="card stat-card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="bar-chart-2" style="width:13px;height:13px;"></i> Resultado Previsto
                </div>
                <div style="font-size:26px; font-weight:700; color:<?= $resultadoPrev >= 0 ? '#10b981' : '#ef4444' ?>;">
                    <?= formatarMoeda($resultadoPrev) ?>
                </div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">Entradas − Saídas previstas</div>
            </div>
        </div>

        <!-- Gráfico de Fluxo + Alertas -->
        <div style="display:grid; grid-template-columns:1fr 380px; gap:20px; margin-bottom:24px;">

            <!-- Gráfico simplificado via CSS/Canvas -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:4px;">Fluxo dos Próximos 30 Dias</h3>
                <p style="font-size:12px; color:#6b7280; margin-bottom:20px;">Entradas e saídas previstas</p>

                <?php if (empty($dadosFluxo)): ?>
                <div style="text-align:center; padding:40px; color:#4b5563;">
                    <i data-lucide="bar-chart-2" style="width:40px;height:40px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    <p>Nenhum lançamento previsto nos próximos 30 dias</p>
                </div>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php
                    $maxVal = max(array_map(fn($r) => max($r['entradas'], $r['saidas']), $dadosFluxo)) ?: 1;
                    foreach ($dadosFluxo as $row):
                        $pctE = ($row['entradas'] / $maxVal) * 100;
                        $pctS = ($row['saidas'] / $maxVal) * 100;
                    ?>
                    <div style="display:flex; align-items:center; gap:12px; font-size:12px;">
                        <div style="width:60px; color:#6b7280; flex-shrink:0;"><?= formatarData(substr($row['data'],0,10)) ?></div>
                        <div style="flex:1;">
                            <?php if ($row['entradas'] > 0): ?>
                            <div style="background:rgba(16,185,129,0.2); height:10px; border-radius:4px; width:<?= round($pctE) ?>%; margin-bottom:3px;" title="Entrada: <?= formatarMoeda($row['entradas']) ?>"></div>
                            <?php endif; ?>
                            <?php if ($row['saidas'] > 0): ?>
                            <div style="background:rgba(239,68,68,0.2); height:10px; border-radius:4px; width:<?= round($pctS) ?>%;" title="Saída: <?= formatarMoeda($row['saidas']) ?>"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex; gap:16px; margin-top:16px; font-size:11px;">
                    <span style="display:flex;align-items:center;gap:6px;color:#10b981;"><span style="width:10px;height:10px;background:rgba(16,185,129,0.4);border-radius:2px;display:inline-block;"></span> Entradas</span>
                    <span style="display:flex;align-items:center;gap:6px;color:#ef4444;"><span style="width:10px;height:10px;background:rgba(239,68,68,0.4);border-radius:2px;display:inline-block;"></span> Saídas</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alertas -->
            <div style="display:flex; flex-direction:column; gap:16px;">

                <!-- Contas Vencidas -->
                <div class="card" style="padding:20px; flex:1;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                        <i data-lucide="alert-circle" style="width:15px;height:15px;color:#ef4444;"></i>
                        <h3 style="font-size:14px; font-weight:600; color:#e2e8f0;">Vencidas</h3>
                        <?php if ($contasVencidas): ?>
                        <span style="background:rgba(239,68,68,0.2); color:#f87171; font-size:11px; padding:2px 8px; border-radius:10px; margin-left:auto;"><?= count($contasVencidas) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($contasVencidas)): ?>
                    <p style="font-size:13px; color:#4b5563; text-align:center; padding:12px 0;">Nenhuma conta vencida 🎉</p>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach (array_slice($contasVencidas, 0, 5) as $c): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                            <div>
                                <div style="color:#e2e8f0; font-weight:500;"><?= sanitizar($c['descricao']) ?></div>
                                <div style="color:#6b7280; font-size:11px;"><?= formatarData($c['vencimento']) ?></div>
                            </div>
                            <div style="color:<?= $c['tipo']==='receber'?'#10b981':'#ef4444' ?>; font-weight:600;"><?= formatarMoeda($c['valor']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vencendo em 7 dias -->
                <div class="card" style="padding:20px; flex:1;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                        <i data-lucide="clock" style="width:15px;height:15px;color:#f59e0b;"></i>
                        <h3 style="font-size:14px; font-weight:600; color:#e2e8f0;">Próximos 7 dias</h3>
                        <?php if ($contasProximas): ?>
                        <span style="background:rgba(245,158,11,0.2); color:#fbbf24; font-size:11px; padding:2px 8px; border-radius:10px; margin-left:auto;"><?= count($contasProximas) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($contasProximas)): ?>
                    <p style="font-size:13px; color:#4b5563; text-align:center; padding:12px 0;">Nada vencendo esta semana</p>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach (array_slice($contasProximas, 0, 5) as $c): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                            <div>
                                <div style="color:#e2e8f0; font-weight:500;"><?= sanitizar($c['descricao']) ?></div>
                                <div style="color:#6b7280; font-size:11px;"><?= formatarData($c['vencimento']) ?></div>
                            </div>
                            <div style="color:<?= $c['tipo']==='receber'?'#10b981':'#ef4444' ?>; font-weight:600;"><?= formatarMoeda($c['valor']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Atalhos rápidos -->
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="financeiro/lancamentos.php" class="btn-primary" style="text-decoration:none;">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Lançamento
            </a>
            <a href="precificacao/simulador.php" class="btn-secondary" style="text-decoration:none;">
                <i data-lucide="sparkles" style="width:15px;height:15px;"></i> Simular Proposta
            </a>
            <a href="financeiro/lancamentos.php?filtro=atrasado" class="btn-danger" style="text-decoration:none;">
                <i data-lucide="alert-triangle" style="width:15px;height:15px;"></i> Ver Vencidas
            </a>
        </div>

    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
