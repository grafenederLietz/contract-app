<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

header('Location: ' . (is_logged_in() ? '/dashboard.php' : '/login.php'));
exit;
