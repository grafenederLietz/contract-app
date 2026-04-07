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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Ungültige Standort-ID.');
}

$error = '';
$success = '';

$stmt = $db->prepare("
    SELECT id, name
    FROM locations
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$location = $result->fetch_assoc();
$stmt->close();

if (!$location) {
    die('Standort nicht gefunden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $name = trim((string)($_POST['name'] ?? ''));

    if ($name === '') {
        $error = 'Bitte einen Standortnamen eingeben.';
    } else {
        $checkStmt = $db->prepare("
            SELECT id
            FROM locations
            WHERE name = ? AND id <> ?
            LIMIT 1
        ");

        if (!$checkStmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $checkStmt->bind_param('si', $name, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingLocation = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingLocation) {
            $error = 'Standort existiert bereits.';
        } else {
            $updateStmt = $db->prepare("
                UPDATE locations
                SET name = ?
                WHERE id = ?
            ");

            if (!$updateStmt) {
                app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
            }

            $updateStmt->bind_param('si', $name, $id);

            if ($updateStmt->execute()) {
                $success = 'Standort wurde gespeichert.';
                $location['name'] = $name;
            } else {
                app_log('db-execute', $updateStmt->error);
                $error = 'Daten konnten nicht gespeichert werden.';
            }

            $updateStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Standort bearbeiten</title>
</head>
<body>

<h1>Standort bearbeiten</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/locations.php">Standortverwaltung</a> |
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
    <label for="name">Standortname</label><br>
    <input
        type="text"
        id="name"
        name="name"
        required
        value="<?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>