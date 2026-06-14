<?php
/**
 * logout.php — Encerra a sessão do usuário e retorna à tela de login.
 */

require_once __DIR__ . '/includes/auth.php'; // recupera a sessão ativa

// Limpa os dados e destrói a sessão
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

redirecionar('index.php');
