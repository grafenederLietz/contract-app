<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

require_login();

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
    die('Zugriff verweigert.');
}

$db = db();

$result = $db->query("
    SELECT id, name
    FROM locations
    ORDER BY name ASC
");

if (!$result) {
    die('SQL Fehler: ' . $db->error);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <link rel="stylesheet" href="/assets/app.css">
    <title>Standortverwaltung</title>
</head>
<body>

<h1>Standortverwaltung</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/location_create.php">Standort anlegen</a> |
    <a href="/logout.php">Logout</a>
</p>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Aktion</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><a href="/location_edit.php?id=<?php echo (int)$row['id']; ?>">Bearbeiten</a></td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>