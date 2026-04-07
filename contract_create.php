<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

if (!can_manage_contracts()) {
    die('Zugriff verweigert.');
}

$db = db();

$userId = current_user_id();
$userRole = current_user_role();

$error = '';
$success = '';

$users = get_active_users($db);
$locations = get_allowed_locations($db, $userId, $userRole);
$departments = get_allowed_departments($db, $userId, $userRole);

$allowedLocationIds = get_allowed_ids($locations);
$allowedDepartmentIds = get_allowed_ids($departments);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $supplier = trim((string)($_POST['supplier'] ?? ''));
    $contract_subject = trim((string)($_POST['contract_subject'] ?? ''));
    $contract_start = (string)($_POST['contract_start'] ?? '');
    $duration_months = (int)($_POST['duration_months'] ?? 0);
    $termination_period_months = (int)($_POST['termination_period_months'] ?? 0);
    $termination_text = trim((string)($_POST['termination_text'] ?? ''));
    $status = (string)($_POST['status'] ?? 'active');
    $responsible_user_id = (int)($_POST['responsible_user_id'] ?? 0);
    $location_ids = $_POST['location_ids'] ?? [];
    $department_ids = $_POST['department_ids'] ?? [];

    if (!is_array($location_ids)) {
        $location_ids = [];
    }

    if (!is_array($department_ids)) {
        $department_ids = [];
    }

    $validatedLocationIds = validate_selected_ids($location_ids, $allowedLocationIds);
    $validatedDepartmentIds = validate_selected_ids($department_ids, $allowedDepartmentIds);

    if ($supplier === '' || $contract_start === '' || $duration_months <= 0) {
        $error = 'Pflichtfelder fehlen.';
    } elseif (!in_array($status, allowed_contract_statuses(), true)) {
        $error = 'Ungültiger Status.';
    } else {
        $contract_end = date('Y-m-d', strtotime($contract_start . " +$duration_months months"));

        $stmt = $db->prepare("
            INSERT INTO contracts
            (
                supplier,
                contract_subject,
                contract_start,
                duration_months,
                contract_end,
                termination_period_months,
                termination_text,
                status,
                responsible_user_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $stmt->bind_param(
            'sssissssi',
            $supplier,
            $contract_subject,
            $contract_start,
            $duration_months,
            $contract_end,
            $termination_period_months,
            $termination_text,
            $status,
            $responsible_user_id
        );

        if ($stmt->execute()) {
            $contractId = (int)$stmt->insert_id;
            $stmt->close();

            if (!empty($validatedLocationIds)) {
                $stmtLoc = $db->prepare("
                    INSERT INTO contract_locations (contract_id, location_id)
                    VALUES (?, ?)
                ");

                if (!$stmtLoc) {
                    app_log('db-prepare-standorte', $db->error);
                    app_abort('Datenbank-Fehler.', 500);
                }

                foreach ($validatedLocationIds as $locId) {
                    $stmtLoc->bind_param('ii', $contractId, $locId);
                    $stmtLoc->execute();
                }

                $stmtLoc->close();
            }

            if (!empty($validatedDepartmentIds)) {
                $stmtDept = $db->prepare("
                    INSERT INTO contract_departments (contract_id, department_id)
                    VALUES (?, ?)
                ");

                if (!$stmtDept) {
                    app_log('db-prepare-abteilungen', $db->error);
                    app_abort('Datenbank-Fehler.', 500);
                }

                foreach ($validatedDepartmentIds as $deptId) {
                    $stmtDept->bind_param('ii', $contractId, $deptId);
                    $stmtDept->execute();
                }

                $stmtDept->close();
            }

            $success = 'Vertrag gespeichert.';
        } else {
            app_log('db-execute', $stmt->error);
                $error = 'Daten konnten nicht gespeichert werden.';
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
    <link rel="stylesheet" href="/assets/app.css">
    <title>Vertrag anlegen</title>
</head>
<body>

<h1>Vertrag anlegen</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/contracts.php">Verträge</a> |
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

    <label for="supplier">Lieferant</label><br>
    <input type="text" id="supplier" name="supplier" required><br><br>

    <label for="contract_subject">Vertragsgegenstand</label><br>
    <textarea id="contract_subject" name="contract_subject" rows="4" cols="60"></textarea><br><br>

    <label for="contract_start">Vertragsbeginn</label><br>
    <input type="date" id="contract_start" name="contract_start" required><br><br>

    <label for="duration_months">Laufzeit (Monate)</label><br>
    <input type="number" id="duration_months" name="duration_months" min="1" required><br><br>

    <label for="termination_period_months">Kündigungsfrist (Monate)</label><br>
    <input type="number" id="termination_period_months" name="termination_period_months" min="0"><br><br>

    <label for="termination_text">Kündigungsbedingungen</label><br>
    <textarea id="termination_text" name="termination_text" rows="4" cols="60"></textarea><br><br>

    <label for="status">Status</label><br>
    <select id="status" name="status">
        <option value="active">Aktiv</option>
        <option value="terminated">Gekündigt</option>
        <option value="ended">Beendet</option>
        <option value="adjustment_required">Anpassung erforderlich</option>
        <option value="archived">Archiviert</option>
    </select><br><br>

    <label for="responsible_user_id">Verantwortlicher</label><br>
    <select id="responsible_user_id" name="responsible_user_id">
        <option value="0">-- auswählen --</option>
        <?php foreach ($users as $userItem): ?>
            <option value="<?php echo (int)$userItem['id']; ?>">
                <?php echo htmlspecialchars((string)$userItem['display_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="location_ids">Standorte</label><br>
    <select id="location_ids" name="location_ids[]" multiple size="5">
        <?php foreach ($locations as $location): ?>
            <option value="<?php echo (int)$location['id']; ?>">
                <?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="department_ids">Abteilungen</label><br>
    <select id="department_ids" name="department_ids[]" multiple size="5">
        <?php foreach ($departments as $department): ?>
            <option value="<?php echo (int)$department['id']; ?>">
                <?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Speichern</button>

</form>

</body>
</html>