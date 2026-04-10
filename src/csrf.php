<?php

declare(strict_types=1);

function csrf_regenerate_token(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        $_SESSION['csrf_token'] === ''
    ) {
        csrf_regenerate_token();
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function verify_csrf_token(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        $submittedToken === '' ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        unset($_SESSION['csrf_token']);
        app_abort('Ungültige Anfrage.', 400);
    }

    csrf_regenerate_token();
}