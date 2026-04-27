<?php
$usuario = usuarioAtual();

// Detectar página atual para highlight do menu
$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivo(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<div class="sidebar flex flex-col" style="width:240px; min-height:100vh; flex-shrink:0; position:sticky; top:0; height:100vh;">

    <!-- Logo -->
    <div style="padding:20px 20px 16px; border-bottom:1px solid rgba(255,255,255,0.06);">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:32px; height:32px; background:linear-gradient(135deg,#7c3aed,#3b82f6); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            </div>
            <div>
                <div style="font-size:15px; font-weight:700; color:#f1f5f9; letter-spacing:-0.3px;">Distinto</div>
                <div style="font-size:10px; color:#6b7280;">Gestão de Agência</div>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav style="flex:1; padding:12px 10px; overflow-y:auto;">

        <div class="nav-section">Principal</div>
        <a href="<?= raizUrl('/dashboard.php') ?>" class="nav-link <?= menuAtivo('/dashboard') ?>">
            <i data-lucide="layout-dashboard" style="width:16px;height:16px;"></i>
            Dashboard
        </a>

        <div class="nav-section" style="margin-top:8px;">Financeiro</div>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="nav-link <?= menuAtivo('/lancamentos') ?>">
            <i data-lucide="arrow-left-right" style="width:16px;height:16px;"></i>
            Lançamentos
        </a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/financeiro/configuracoes') ?>">
            <i data-lucide="building-2" style="width:16px;height:16px;"></i>
            Custos Fixos
        </a>

        <div class="nav-section" style="margin-top:8px;">Precificação</div>
        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" class="nav-link <?= menuAtivo('/servicos') ?>">
            <i data-lucide="briefcase" style="width:16px;height:16px;"></i>
            Serviços
        </a>
        <a href="<?= raizUrl('/precificacao/simulador.php') ?>" class="nav-link <?= menuAtivo('/simulador') ?>">
            <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
            Simulador IA
        </a>

        <div class="nav-section" style="margin-top:8px;">Em Breve</div>
        <?php foreach(['Clientes','Contratos','Projetos','Relatórios'] as $item): ?>
        <div class="nav-link" style="opacity:0.4; cursor:not-allowed;">
            <i data-lucide="clock" style="width:16px;height:16px;"></i>
            <?= $item ?>
            <span style="margin-left:auto; font-size:9px; background:rgba(124,58,237,0.3); color:#a78bfa; padding:2px 6px; border-radius:4px;">Logo</span>
        </div>
        <?php endforeach; ?>

        <div class="nav-section" style="margin-top:8px;">Sistema</div>
        <a href="<?= raizUrl('/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/configuracoes') ?>">
            <i data-lucide="settings" style="width:16px;height:16px;"></i>
            Configurações
        </a>

    </nav>

    <!-- Usuário + logout -->
    <div style="padding:14px 12px; border-top:1px solid rgba(255,255,255,0.06);">
        <div style="display:flex; align-items:center; gap:10px; padding:10px; background:rgba(255,255,255,0.04); border-radius:10px;">
            <div style="width:32px; height:32px; background:linear-gradient(135deg,#7c3aed,#3b82f6); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:white; flex-shrink:0;">
                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
            </div>
            <div style="min-width:0; flex:1;">
                <div style="font-size:13px; font-weight:500; color:#e2e8f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['nome']) ?></div>
                <div style="font-size:11px; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['email']) ?></div>
            </div>
            <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" style="color:#6b7280; flex-shrink:0;">
                <i data-lucide="log-out" style="width:15px;height:15px;"></i>
            </a>
        </div>
    </div>
</div>
