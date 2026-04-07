<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

$db = db();

$user = current_user();
$userId = current_user_id();
$userRole = current_user_role();

$contracts = load_contract_rows(
    $db,
    $userId,
    $userRole,
    '',
    '',
    0,
    0
);

$trafficLightCounts = [
    'Grün' => 0,
    'Gelb' => 0,
    'Rot' => 0,
    'Überfällig' => 0,
    'Grau' => 0,
];

foreach ($contracts as $row) {
    $trafficLightCounts[(string)$row['traffic_light']['label']]++;
}

$totalContracts = count($contracts);

$urgentContracts = [];

foreach ($contracts as $row) {
    if (in_array((string)$row['traffic_light']['label'], ['Rot', 'Überfällig'], true)) {
        $urgentContracts[] = $row;
    }
}

$urgentContracts = array_slice($urgentContracts, 0, 25);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <title>Dashboard</title>
</head>
<body>

<h1>Dashboard</h1>

<p>
    Angemeldet als:
    <?php echo htmlspecialchars((string)$user['display_name'], ENT_QUOTES, 'UTF-8'); ?>
    (<?php echo htmlspecialchars((string)$user['role'], ENT_QUOTES, 'UTF-8'); ?>)
</p>

<nav class="top-nav">
    <a href="/dashboard.php">Dashboard</a>

    <details>
        <summary>Vertragsverwaltung</summary>
        <a href="/contracts.php">Verträge anzeigen</a>
        <?php if (can_manage_contracts()): ?>
            <a href="/contract_create.php">Neuer Vertrag</a>
        <?php endif; ?>
    </details>

    <?php if ($userRole === 'admin'): ?>
        <details>
            <summary>Userverwaltung</summary>
            <a href="/users.php">Benutzerverwaltung</a>
        </details>

        <details>
            <summary>Stammdatenverwaltung</summary>
            <a href="/locations.php">Standorte</a>
            <a href="/departments.php">Abteilungen</a>
        </details>
    <?php endif; ?>

    <a href="/logout.php">Logout</a>
</nav>

<h2>Ampelübersicht</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Gesamt</th>
        <th style="background-color: #388e3c; color: #ffffff;">Grün</th>
        <th style="background-color: #fbc02d;">Gelb</th>
        <th style="background-color: #d32f2f; color: #ffffff;">Rot</th>
        <th style="background-color: #7f0000; color: #ffffff;">Überfällig</th>
        <th style="background-color: #9e9e9e; color: #ffffff;">Grau</th>
    </tr>
    <tr>
        <td><?php echo $totalContracts; ?></td>
        <td><?php echo $trafficLightCounts['Grün']; ?></td>
        <td><?php echo $trafficLightCounts['Gelb']; ?></td>
        <td><?php echo $trafficLightCounts['Rot']; ?></td>
        <td><?php echo $trafficLightCounts['Überfällig']; ?></td>
        <td><?php echo $trafficLightCounts['Grau']; ?></td>
    </tr>
</table>

<h2>Kritische Verträge</h2>

<?php if ($urgentContracts === []): ?>
    <p>Keine roten oder überfälligen Verträge vorhanden.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Lieferant</th>
            <th>Vertragsgegenstand</th>
            <th>Vertragsende</th>
            <th>Spätester Kündigungstermin</th>
            <th>Ampel</th>
            <th>Tage bis Frist</th>
            <th>Verantwortlich</th>
            <th>Aktion</th>
        </tr>

        <?php foreach ($urgentContracts as $row): ?>
            <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars((string)$row['supplier'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['contract_subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['contract_end'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['latest_termination_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="background-color: <?php echo htmlspecialchars((string)$row['traffic_light']['color'], ENT_QUOTES, 'UTF-8'); ?>; color: #ffffff; font-weight: bold;">
                    <?php echo htmlspecialchars((string)$row['traffic_light']['label'], ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td><?php echo $row['traffic_light']['days'] === null ? '-' : (int)$row['traffic_light']['days']; ?></td>
                <td><?php echo htmlspecialchars((string)($row['responsible_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><a href="/contract_edit.php?id=<?php echo (int)$row['id']; ?>">Bearbeiten</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
