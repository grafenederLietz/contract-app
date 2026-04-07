<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

require_login();

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
    die('Zugriff verweigert.');
}

$db = db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));

    if ($name === '') {
        $error = 'Bitte einen Standortnamen eingeben.';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM locations WHERE name = ? LIMIT 1");

        if (!$checkStmt) {
            die('Prepare-Fehler: ' . $db->error);
        }

        $checkStmt->bind_param('s', $name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingLocation = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingLocation) {
            $error = 'Standort existiert bereits.';
        } else {
            $stmt = $db->prepare("INSERT INTO locations (name) VALUES (?)");

            if (!$stmt) {
                die('Prepare-Fehler: ' . $db->error);
            }

            $stmt->bind_param('s', $name);

            if ($stmt->execute()) {
                $success = 'Standort wurde angelegt.';
            } else {
                $error = 'Fehler beim Speichern: ' . $stmt->error;
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
    <title>Standort anlegen</title>
</head>
<body>

<h1>Standort anlegen</h1>

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
    <label for="name">Standortname</label><br>
    <input type="text" id="name" name="name" required><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>