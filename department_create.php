<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

require_login();

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
    die('Zugriff verweigert.');
}

$db = db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $name = trim((string)($_POST['name'] ?? ''));

    if ($name === '') {
        $error = 'Bitte einen Abteilungsnamen eingeben.';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");

        if (!$checkStmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $checkStmt->bind_param('s', $name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingDepartment = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingDepartment) {
            $error = 'Abteilung existiert bereits.';
        } else {
            $stmt = $db->prepare("INSERT INTO departments (name) VALUES (?)");

            if (!$stmt) {
                app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
            }

            $stmt->bind_param('s', $name);

            if ($stmt->execute()) {
                $success = 'Abteilung wurde angelegt.';
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
    <link rel="stylesheet" href="/assets/app.css">
    <title>Abteilung anlegen</title>
</head>
<body>

<h1>Abteilung anlegen</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/departments.php">Abteilungsverwaltung</a> |
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
    <label for="name">Abteilungsname</label><br>
    <input type="text" id="name" name="name" required><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>