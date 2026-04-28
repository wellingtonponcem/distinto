<?php
require_once __DIR__ . '/env.php';

function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function estaAutenticado(): bool {
    iniciarSessao();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function exigirAutenticacao(): void {
    if (!estaAutenticado()) {
        // Se for uma requisição de API, responde com JSON em vez de redirecionar
        if (str_contains($_SERVER['SCRIPT_NAME'], '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'Sessão expirada ou não autenticado. Faça login novamente.']);
            exit;
        }

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        // Calcula profundidade do arquivo atual para achar o index.php
        $depth = substr_count(str_replace($base, '', $_SERVER['SCRIPT_NAME']), '/') - 1;
        $prefix = str_repeat('../', max(0, $depth));
        header('Location: ' . $prefix . 'index.php');
        exit;
    }
}

function usuarioAtual(): array {
    iniciarSessao();
    return [
        'id'   => $_SESSION['user_id'] ?? '',
        'nome' => $_SESSION['user_nome'] ?? '',
        'email'=> $_SESSION['user_email'] ?? '',
        'nivel'=> $_SESSION['user_nivel'] ?? 0,
    ];
}

function logarUsuario(array $user): void {
    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nome']  = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nivel'] = $user['nivel'] ?? 0;
}

function deslogarUsuario(): void {
    iniciarSessao();
    session_unset();
    session_destroy();
}
