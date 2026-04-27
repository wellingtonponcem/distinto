<?php
$tituloPagina = $tituloPagina ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizar($tituloPagina) ?> — <?= APP_NAME ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        distinto: {
                            bg:       '#0c0c18',
                            surface:  '#13131f',
                            card:     '#1a1a2e',
                            border:   '#ffffff0f',
                            primary:  '#7c3aed',
                            secondary:'#3b82f6',
                            muted:    '#94a3b8',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        body { background-color: #0c0c18; color: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }
        .sidebar { background-color: #13131f; border-right: 1px solid #ffffff0f; }
        .card { background-color: #1a1a2e; border: 1px solid #ffffff0f; border-radius: 12px; }
        .card-hover { transition: border-color 0.2s; }
        .card-hover:hover { border-color: rgba(124,58,237,0.4); }
        .nav-link { display:flex; align-items:center; gap:10px; padding:10px 16px; border-radius:8px; color:#94a3b8; text-decoration:none; font-size:14px; transition:all 0.15s; }
        .nav-link:hover, .nav-link.ativo { background:rgba(124,58,237,0.15); color:#a78bfa; }
        .nav-link.ativo { border-left: 2px solid #7c3aed; padding-left: 14px; }
        .nav-section { font-size:10px; font-weight:600; color:#4b5563; text-transform:uppercase; letter-spacing:0.1em; padding: 16px 16px 6px; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); color:white; padding:8px 18px; border-radius:8px; font-size:14px; font-weight:500; border:none; cursor:pointer; transition:opacity 0.15s; display:inline-flex; align-items:center; gap:6px; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: rgba(255,255,255,0.06); color:#e2e8f0; padding:8px 18px; border-radius:8px; font-size:14px; font-weight:500; border:1px solid rgba(255,255,255,0.1); cursor:pointer; transition:all 0.15s; display:inline-flex; align-items:center; gap:6px; }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }
        .btn-danger { background: rgba(239,68,68,0.15); color:#f87171; padding:8px 18px; border-radius:8px; font-size:14px; font-weight:500; border:1px solid rgba(239,68,68,0.3); cursor:pointer; transition:all 0.15s; }
        .btn-danger:hover { background: rgba(239,68,68,0.25); }
        .input { background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#f1f5f9; border-radius:8px; padding:9px 14px; font-size:14px; width:100%; outline:none; transition: border-color 0.15s; }
        .input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
        .input::placeholder { color: #4b5563; }
        .select { background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#f1f5f9; border-radius:8px; padding:9px 14px; font-size:14px; width:100%; outline:none; cursor:pointer; }
        .select:focus { border-color: #7c3aed; }
        .select option { background: #1a1a2e; color: #f1f5f9; }
        .label { font-size:12px; font-weight:500; color:#94a3b8; margin-bottom:6px; display:block; }
        .badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; display:inline-block; }
        .table-header { font-size:11px; font-weight:600; color:#4b5563; text-transform:uppercase; letter-spacing:0.05em; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .table-row { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.1s; }
        .table-row:hover { background: rgba(255,255,255,0.03); }
        .table-cell { padding: 14px 16px; font-size:14px; color: #cbd5e1; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
        .modal { background:#1a1a2e; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; padding:28px; }
        .stat-card { position:relative; overflow:hidden; }
        .stat-card::before { content:''; position:absolute; top:-50%; right:-20%; width:120px; height:120px; border-radius:50%; opacity:0.08; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius:3px; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen">
