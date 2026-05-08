<?php

declare(strict_types=1);

function password_policy_errors(string $password): array
{
    $errors = [];

    if (strlen($password) < 12) {
        $errors[] = 'mindestens 12 Zeichen';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'mindestens ein Großbuchstabe';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'mindestens ein Kleinbuchstabe';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'mindestens eine Zahl';
    }

    return $errors;
}

function validate_password(string $password): bool
{
    return password_policy_errors($password) === [];
}