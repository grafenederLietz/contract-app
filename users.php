<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

require_login();

$user = current_user();

if (($user['role'] ?? '') !== 'admin') {
    die('Zugriff verweigert.');
}

$db = db();

$result = $db->query("
    SELECT
        id,
        username,
        display_name,
        email,
        role,
        is_active,
        created_at,
        last_login_at
    FROM users
    ORDER BY username ASC
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
    <title>Benutzerverwaltung</title>
</head>
<body>

<h1>Benutzerverwaltung</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/user_create.php">Benutzer anlegen</a> |
    <a href="/logout.php">Logout</a>
</p>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>E-Mail</th>
        <th>Rolle</th>
        <th>Aktiv</th>
        <th>Erstellt</th>
        <th>Letzter Login</th>
        <th>Aktion</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$row['display_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$row['role'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int)$row['is_active'] === 1 ? 'Ja' : 'Nein'; ?></td>
            <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['last_login_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><a href="/user_edit.php?id=<?php echo (int)$row['id']; ?>">Bearbeiten</a></td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>