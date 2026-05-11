<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

require_login();

$user = current_user();

if (($user['role'] ?? '') !== 'admin') {
    app_abort('Zugriff verweigert.', 403);
}

$db = db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $username = trim((string)($_POST['username'] ?? ''));
    $display_name = trim((string)($_POST['display_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = trim((string)($_POST['role'] ?? 'viewer'));
    $password = (string)($_POST['password'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $allowedRoles = ['admin', 'editor', 'viewer'];

    if ($username === '' || $display_name === '' || $password === '') {
        $error = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Ungültige Rolle.';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if (!$checkStmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $checkStmt->bind_param('s', $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingUser = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            $error = 'Benutzername existiert bereits.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users
                (username, password_hash, display_name, email, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
            }

            $stmt->bind_param(
                'sssssi',
                $username,
                $password_hash,
                $display_name,
                $email,
                $role,
                $is_active
            );

            if ($stmt->execute()) {
                $success = 'Benutzer wurde angelegt.';
            } else {
                app_log('db-execute', $stmt->error);
                $error = 'Daten konnten nicht gespeichert werden.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <title>Benutzer anlegen</title>
</head>
<body>

<h1>Benutzer anlegen</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/users.php">Benutzerverwaltung</a> |
    <a href="/logout.php">Logout</a>
</p>

<?php if ($error !== ''): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post">
    <?php echo csrf_input(); ?>

    <label for="username">Benutzername</label><br>
    <input type="text" id="username" name="username" required><br><br>

    <label for="display_name">Anzeigename</label><br>
    <input type="text" id="display_name" name="display_name" required><br><br>

    <label for="email">E-Mail</label><br>
    <input type="email" id="email" name="email"><br><br>

    <label for="role">Rolle</label><br>
    <select id="role" name="role">
        <option value="admin">Admin</option>
        <option value="editor">Bearbeiter</option>
        <option value="viewer">Leser</option>
    </select><br><br>

    <label for="password">Passwort</label><br>
    <input type="password" id="password" name="password" required><br><br>

    <label>
        <input type="checkbox" name="is_active" checked>
        Aktiv
    </label><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>