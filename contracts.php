<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

$db = db();

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
    app_abort('Zugriff verweigert.', 403);
}

if ($departmentFilter > 0 && !in_array($departmentFilter, $allowedDepartmentIds, true)) {
    app_abort('Zugriff verweigert.', 403);
}

$contracts = load_contract_rows(
    $db,
    $userId,
    $userRole,
    $supplierFilter,
    $statusFilter,
    $locationFilter,
    $departmentFilter
);

if ($trafficLightFilter !== '' && in_array($trafficLightFilter, allowed_traffic_light_labels(), true)) {
    $contracts = array_values(array_filter(
        $contracts,
        static function (array $row) use ($trafficLightFilter): bool {
            return (string)$row['traffic_light']['label'] === $trafficLightFilter;
        }
    ));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/app.css">
    <title>Verträge</title>
</head>
<body>

<h1>Verträge</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <?php if (can_manage_contracts()): ?>
        <a href="/contract_create.php">Neuen Vertrag anlegen</a> |
    <?php endif; ?>
    <a href="/logout.php">Logout</a>
</p>

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

    <button type="submit">Filter anwenden</button>
    <a href="/contracts.php">Filter zurücksetzen</a>
</form>

<h2>Ergebnis (<?php echo count($contracts); ?>)</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th>
        <th>Lieferant</th>
        <th>Betreff</th>
        <th>Vertragsende</th>
        <th>Kündigungsstichtag</th>
        <th>Ampel</th>
        <th>Status</th>
        <th>Verantwortlich</th>
        <th>Aktion</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($contracts === []): ?>
        <tr>
            <td colspan="9">Keine Verträge gefunden.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($contracts as $row): ?>
            <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars((string)$row['supplier'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['contract_subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['contract_end'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$row['latest_termination_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="color: <?php echo htmlspecialchars((string)$row['traffic_light']['color'], ENT_QUOTES, 'UTF-8'); ?>; font-weight: bold;">
                    <?php echo htmlspecialchars((string)$row['traffic_light']['label'], ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td><?php echo htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($row['responsible_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <a href="/contract_edit.php?id=<?php echo (int)$row['id']; ?>">Öffnen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
