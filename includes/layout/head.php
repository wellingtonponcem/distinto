<?php
$tituloPagina = $tituloPagina ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizar($tituloPagina) ?> - <?= APP_NAME ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        distinto: {
                            ink: '#111111',
                            paper: '#ffffff',
                            line: '#ececec',
                            muted: '#777777'
                        }
                    }
                }
            }
        };
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        * { box-sizing: border-box; }

        html {
            background: #050505;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 78% 0%, rgba(255,255,255,0.18), transparent 26rem),
                linear-gradient(180deg, #949494 0, #949494 154px, #050505 154px, #050505 100%);
            color: #111111;
            font-family: 'Outfit', Arial, sans-serif;
            overflow-x: hidden;
        }

        body::before {
            content: "DISTINTO";
            position: fixed;
            top: 42px;
            left: min(31vw, 440px);
            z-index: 0;
            color: rgba(255,255,255,0.34);
            font-size: clamp(34px, 5vw, 76px);
            font-weight: 800;
            letter-spacing: -0.05em;
            line-height: 0.92;
            pointer-events: none;
        }

        body::after {
            content: "ERP SaaS";
            position: fixed;
            top: 34px;
            right: 32px;
            z-index: 0;
            color: #ffffff;
            font-size: clamp(44px, 8vw, 102px);
            font-weight: 800;
            letter-spacing: -0.06em;
            line-height: 0.85;
            pointer-events: none;
        }

        #app-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
            padding-top: 154px;
            background: transparent;
        }

        .sidebar {
            width: 248px !important;
            min-height: calc(100vh - 154px) !important;
            height: calc(100vh - 154px) !important;
            margin-left: 56px;
            flex-shrink: 0;
            position: sticky;
            top: 154px;
            color: #b8b8b8;
            background: #171717;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 28px 0 0 28px;
            box-shadow: 0 28px 60px rgba(0,0,0,0.38);
            overflow: hidden;
        }

        #main-content,
        .content-sheet {
            flex: 1;
            min-width: 0;
            min-height: calc(100vh - 176px);
            margin: 0 40px 22px 0;
            padding: 34px 36px !important;
            overflow-y: auto;
            max-width: none !important;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 0 0 0 28px;
            box-shadow: 0 28px 60px rgba(0,0,0,0.36);
        }

        .app-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: -14px -12px 28px;
            padding: 0 2px 18px;
            border-bottom: 1px solid #eeeeee;
        }

        .top-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            overflow-x: auto;
        }

        .top-nav a {
            flex: 0 0 auto;
            color: #222222;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 999px;
        }

        .top-nav a:hover {
            background: #f4f4f4;
        }

        .page-title {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #111111;
        }

        .page-subtitle {
            margin-top: 4px;
            color: #8a8a8a;
            font-size: 13px;
            font-weight: 500;
        }

        .card {
            background: #ffffff;
            border: 1px solid #ececec;
            border-radius: 15px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.02);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .card:hover {
            border-color: #dddddd;
            box-shadow: 0 16px 30px rgba(0,0,0,0.05);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            border-radius: 10px;
            color: #969696;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.18s ease;
        }

        .nav-link:hover,
        .nav-link.ativo {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }

        .nav-section {
            padding: 22px 13px 8px;
            color: #626262;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .btn-primary,
        .btn-secondary {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }

        .btn-primary {
            background: #111111;
            color: #ffffff;
            border-color: #111111;
        }

        .btn-primary:hover {
            background: #000000;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #111111;
            border-color: #eeeeee;
        }

        .btn-secondary:hover {
            background: #f7f7f7;
            border-color: #dddddd;
        }

        .trend-up,
        .trend-down {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .trend-up { color: #008758; background: #ecfbf4; }
        .trend-down { color: #c43b3b; background: #fff1f1; }

        .input,
        .select {
            width: 100%;
            min-height: 40px;
            padding: 9px 12px;
            border: 1px solid #e5e5e5;
            border-radius: 9px;
            background: #ffffff;
            color: #111111;
            font-size: 13px;
            outline: none;
        }

        .input:focus,
        .select:focus {
            border-color: #111111;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
        }

        .label {
            display: block;
            margin-bottom: 6px;
            color: #555555;
            font-size: 12px;
            font-weight: 800;
        }

        .table-header {
            padding: 13px 16px;
            background: #f7f7f7;
            color: #777777;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #eeeeee;
        }

        .table-row {
            border-bottom: 1px solid #eeeeee;
        }

        .table-cell {
            padding: 14px 16px;
            font-size: 13px;
            color: #222222;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(0,0,0,0.56);
            backdrop-filter: blur(10px);
        }

        .modal {
            width: min(720px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            padding: 24px;
            background: #ffffff;
            color: #111111;
            border-radius: 18px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }

        #main-content [style*="#f1f5f9"],
        #main-content [style*="#e2e8f0"],
        #main-content [style*="#cbd5e1"] {
            color: #111111 !important;
        }

        #main-content [style*="#94a3b8"],
        #main-content [style*="#6b7280"],
        #main-content [style*="#4b5563"] {
            color: #777777 !important;
        }

        #main-content [style*="rgba(255,255,255,0.04)"],
        #main-content [style*="rgba(255,255,255,0.05)"] {
            background: #ffffff !important;
        }

        #main-content [style*="rgba(255,255,255,0.06)"],
        #main-content [style*="rgba(255,255,255,0.1)"] {
            border-color: #eeeeee !important;
        }

        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.18); border-radius: 999px; }

        @media (max-width: 1024px) {
            body::before,
            body::after { display: none; }

            body {
                background: #050505;
            }

            #app-wrapper {
                padding-top: 16px;
            }

            .sidebar {
                width: 92px !important;
                margin-left: 12px;
                top: 16px;
                height: calc(100vh - 32px) !important;
                min-height: calc(100vh - 32px) !important;
            }

            .sidebar .sidebar-copy,
            .sidebar .nav-section,
            .sidebar .nav-label,
            .sidebar .user-meta {
                display: none;
            }

            .nav-link {
                justify-content: center;
                padding: 12px;
            }

            #main-content,
            .content-sheet {
                margin-right: 12px;
                padding: 24px 18px !important;
            }
        }

        @media (max-width: 760px) {
            #app-wrapper {
                display: block;
                padding: 0;
            }

            .sidebar {
                position: relative;
                top: auto;
                width: auto !important;
                height: auto !important;
                min-height: auto !important;
                margin: 10px;
                border-radius: 22px;
            }

            .sidebar nav {
                display: flex;
                gap: 6px;
                overflow-x: auto;
            }

            #main-content,
            .content-sheet {
                min-height: auto;
                margin: 10px;
                border-radius: 22px;
            }

            .app-topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
