<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['display_name'], $_SESSION['role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => (int)$_SESSION['user_id'],
        'username' => (string)$_SESSION['username'],
        'display_name' => (string)$_SESSION['display_name'],
        'role' => (string)$_SESSION['role'],
    ];
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['display_name'] = (string)($user['display_name'] ?? '');
    $_SESSION['role'] = (string)$user['role'];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();
}