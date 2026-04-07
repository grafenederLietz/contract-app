<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

$db = db();

$user = current_user();
$userId = current_user_id();
$userRole = current_user_role();

$supplierFilter = trim((string)($_GET['supplier'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$trafficLightFilter = trim((string)($_GET['traffic_light'] ?? ''));
$locationFilter = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
$departmentFilter = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

$filterLocations = get_allowed_locations($db, $userId, $userRole);
$filterDepartments = get_allowed_departments($db, $userId, $userRole);
$allowedLocationIds = get_allowed_ids($filterLocations);
$allowedDepartmentIds = get_allowed_ids($filterDepartments);

if ($locationFilter > 0 && !in_array($locationFilter, $allowedLocationIds, true)) {
    die('Zugriff verweigert.');
}

if ($departmentFilter > 0 && !in_array($departmentFilter, $allowedDepartmentIds, true)) {
    die('Zugriff verweigert.');
}

$contracts = load_contract_rows(
    $db,
    $userId,
    $userRole,
    '',
    '',
    0,
    0
);

    $supplierFilter,
    $statusFilter,
    $locationFilter,
    $departmentFilter
);

if ($trafficLightFilter !== '' && in_array($trafficLightFilter, allowed_traffic_light_labels(), true)) {
    $contracts = array_values(array_filter(
        $contracts,
        static function (array $row) use ($trafficLightFilter): bool {
            return $row['traffic_light']['label'] === $trafficLightFilter;
        }
    ));
}

$trafficLightCounts = [
    'Grün' => 0,
    'Gelb' => 0,
    'Rot' => 0,
    'Überfällig' => 0,
    'Grau' => 0,
];

foreach ($contracts as $row) {
    $trafficLightCounts[(string)$row['traffic_light']['label']]++;
    $trafficLightCounts[$row['traffic_light']['label']]++;
}

$totalContracts = count($contracts);

$urgentContracts = [];

foreach ($contracts as $row) {
    if (in_array((string)$row['traffic_light']['label'], ['Rot', 'Überfällig'], true)) {
        $urgentContracts[] = $row;
    }
}

$urgentContracts = array_slice($urgentContracts, 0, 25);
$urgentContracts = array_values(array_filter(
    $contracts,
    static function (array $row): bool {
        return in_array((string)$row['traffic_light']['label'], ['Rot', 'Überfällig'], true);
    }
));

$urgentContracts = array_slice($urgentContracts, 0, 25);
        return in_array($row['traffic_light']['label'], ['Rot', 'Überfällig'], true);
    }
));

$urgentContracts = array_slice($urgentContracts, 0, 10);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <link rel="stylesheet" href="/assets/app.css">
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
<hr>

<h2>Navigation</h2>

<ul>
    <li><a href="/contracts.php">Verträge anzeigen</a></li>

    <?php if (can_manage_contracts()): ?>
        <li><a href="/contract_create.php">Neuen Vertrag anlegen</a></li>
    <?php endif; ?>

    <?php if ($userRole === 'admin'): ?>
        <li><a href="/users.php">Benutzerverwaltung</a></li>
        <li><a href="/locations.php">Standortverwaltung</a></li>
        <li><a href="/departments.php">Abteilungsverwaltung</a></li>
    <?php endif; ?>

    <li><a href="/logout.php">Logout</a></li>
</ul>

<hr>

<h2>Filter</h2>

<form method="get" action="">
    <label for="supplier">Lieferant</label><br>
    <input
        type="text"
        id="supplier"
        name="supplier"
        value="<?php echo htmlspecialchars($supplierFilter, ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <label for="status">Status</label><br>
    <select id="status" name="status">
        <option value="">-- alle --</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktiv</option>
        <option value="terminated" <?php echo $statusFilter === 'terminated' ? 'selected' : ''; ?>>Gekündigt</option>
        <option value="ended" <?php echo $statusFilter === 'ended' ? 'selected' : ''; ?>>Beendet</option>
        <option value="adjustment_required" <?php echo $statusFilter === 'adjustment_required' ? 'selected' : ''; ?>>Anpassung erforderlich</option>
        <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archiviert</option>
    </select><br><br>

    <label for="traffic_light">Ampel</label><br>
    <select id="traffic_light" name="traffic_light">
        <option value="">-- alle --</option>
        <option value="Grün" <?php echo $trafficLightFilter === 'Grün' ? 'selected' : ''; ?>>Grün</option>
        <option value="Gelb" <?php echo $trafficLightFilter === 'Gelb' ? 'selected' : ''; ?>>Gelb</option>
        <option value="Rot" <?php echo $trafficLightFilter === 'Rot' ? 'selected' : ''; ?>>Rot</option>
        <option value="Überfällig" <?php echo $trafficLightFilter === 'Überfällig' ? 'selected' : ''; ?>>Überfällig</option>
        <option value="Grau" <?php echo $trafficLightFilter === 'Grau' ? 'selected' : ''; ?>>Grau</option>
    </select><br><br>

    <label for="location_id">Standort</label><br>
    <select id="location_id" name="location_id">
        <option value="0">-- alle --</option>
        <?php foreach ($filterLocations as $location): ?>
            <option value="<?php echo (int)$location['id']; ?>" <?php echo $locationFilter === (int)$location['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="department_id">Abteilung</label><br>
    <select id="department_id" name="department_id">
        <option value="0">-- alle --</option>
        <?php foreach ($filterDepartments as $department): ?>
            <option value="<?php echo (int)$department['id']; ?>" <?php echo $departmentFilter === (int)$department['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Filtern</button>
    <a href="/dashboard.php">Filter zurücksetzen</a>
</form>

<hr>

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
<hr>

<h2>Kritische Verträge</h2>

<?php if (empty($urgentContracts)): ?>
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
</html>
