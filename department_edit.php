<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

require_login();

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
    die('Zugriff verweigert.');
}

$db = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Ungültige Abteilungs-ID.');
}

$error = '';
$success = '';

$stmt = $db->prepare("
    SELECT id, name
    FROM departments
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Prepare-Fehler: ' . $db->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();
$stmt->close();

if (!$department) {
    die('Abteilung nicht gefunden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));

    if ($name === '') {
        $error = 'Bitte einen Abteilungsnamen eingeben.';
    } else {
        $checkStmt = $db->prepare("
            SELECT id
            FROM departments
            WHERE name = ? AND id <> ?
            LIMIT 1
        ");

        if (!$checkStmt) {
            die('Prepare-Fehler: ' . $db->error);
        }

        $checkStmt->bind_param('si', $name, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingDepartment = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingDepartment) {
            $error = 'Abteilung existiert bereits.';
        } else {
            $updateStmt = $db->prepare("
                UPDATE departments
                SET name = ?
                WHERE id = ?
            ");

            if (!$updateStmt) {
                die('Prepare-Fehler: ' . $db->error);
            }

            $updateStmt->bind_param('si', $name, $id);

            if ($updateStmt->execute()) {
                $success = 'Abteilung wurde gespeichert.';
                $department['name'] = $name;
            } else {
                $error = 'Fehler beim Speichern: ' . $updateStmt->error;
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
    <title>Abteilung bearbeiten</title>
</head>
<body>

<h1>Abteilung bearbeiten</h1>

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
    <label for="name">Abteilungsname</label><br>
    <input
        type="text"
        id="name"
        name="name"
        required
        value="<?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>