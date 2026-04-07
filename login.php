<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Bitte Benutzername und Passwort eingeben.';
    } else {
        $db = db();

        $stmt = db_prepare($db, '
            SELECT id, username, password_hash, display_name, role, is_active
            FROM users
            WHERE username = ?
            LIMIT 1
        ', 'login_user_lookup');

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Ungültige Anmeldedaten.';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'Benutzer ist deaktiviert.';
        } elseif (!password_verify($password, (string)$user['password_hash'])) {
            $error = 'Ungültige Anmeldedaten.';
        } else {
            login_user($user);

            $stmt = db_prepare($db, '
                UPDATE users
                SET last_login_at = NOW()
                WHERE id = ?
            ', 'login_update_last_login');

            $userId = (int)$user['id'];
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();

            header('Location: /dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <?php echo csrf_input(); ?>

        <div>
            <label for="username">Benutzername</label><br>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>

        <br>

        <div>
            <label for="password">Passwort</label><br>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <br>

        <button type="submit">Anmelden</button>
    </form>
</body>
</html>