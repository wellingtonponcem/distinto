<?php
$usuario = usuarioAtual();

$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivo(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<aside class="sidebar flex flex-col">
    <div style="padding:18px 18px 14px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div>
                <div style="color:#ffffff; font-size:20px; font-weight:800; letter-spacing:-0.04em;">DISTINTO</div>
                <div class="sidebar-copy" style="margin-top:2px; color:#686868; font-size:10px; font-weight:800; letter-spacing:0.08em;">AGENCY ERP</div>
            </div>
            <div style="width:30px; height:30px; display:grid; place-items:center; border-radius:999px; background:#2a2a2a; color:#e8e8e8;">
                <i data-lucide="menu" style="width:16px;height:16px;"></i>
            </div>
        </div>
    </div>

    <nav style="flex:1; padding:8px 12px; overflow-y:auto;">
        <div class="nav-section">Principal</div>
        <a href="<?= raizUrl('/dashboard.php') ?>" class="nav-link <?= menuAtivo('/dashboard') ?>">
            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section">Financeiro</div>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="nav-link <?= menuAtivo('/lancamentos') ?>">
            <i data-lucide="receipt-text" style="width:17px;height:17px;"></i>
            <span class="nav-label">Lancamentos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/financeiro/configuracoes') ?>">
            <i data-lucide="calculator" style="width:17px;height:17px;"></i>
            <span class="nav-label">Custos Fixos</span>
        </a>

        <div class="nav-section">Servicos</div>
        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" class="nav-link <?= menuAtivo('/servicos') ?>">
            <i data-lucide="package" style="width:17px;height:17px;"></i>
            <span class="nav-label">Tabela de Precos</span>
        </a>
        <a href="<?= raizUrl('/precificacao/simulador.php') ?>" class="nav-link <?= menuAtivo('/simulador') ?>">
            <i data-lucide="sparkles" style="width:17px;height:17px;"></i>
            <span class="nav-label">Simulador IA</span>
        </a>

        <div class="nav-section">Sistema</div>
        <a href="<?= raizUrl('/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/configuracoes') ?>">
            <i data-lucide="settings" style="width:17px;height:17px;"></i>
            <span class="nav-label">Ajustes</span>
        </a>
    </nav>

    <div style="padding:14px 14px 18px; border-top:1px solid rgba(255,255,255,0.06);">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:34px; height:34px; display:grid; place-items:center; flex-shrink:0; border-radius:999px; background:#f6f6f6; color:#111111; font-size:13px; font-weight:800;">
                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
            </div>
            <div class="user-meta" style="min-width:0; flex:1;">
                <div style="color:#ffffff; font-size:12px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['nome']) ?></div>
                <div style="color:#777777; font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['email']) ?></div>
            </div>
            <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" style="display:grid; place-items:center; width:28px; height:28px; flex-shrink:0; color:#868686; border-radius:8px; text-decoration:none;">
                <i data-lucide="log-out" style="width:15px;height:15px;"></i>
            </a>
        </div>
    </div>
</aside>
