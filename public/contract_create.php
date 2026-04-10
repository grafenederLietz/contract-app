<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

if (!can_manage_contracts()) {
    app_abort('Zugriff verweigert.', 403);
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
    $termination_period_months = (int)($_POST['termination_period_months'] ?? -1);
    $termination_text = trim((string)($_POST['termination_text'] ?? ''));
    $status = (string)($_POST['status'] ?? '');
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

    if (
        $supplier === '' ||
        $contract_subject === '' ||
        $contract_start === '' ||
        $duration_months <= 0 ||
        $termination_period_months < 0 ||
        $termination_text === '' ||
        $responsible_user_id <= 0 ||
        $validatedLocationIds === [] ||
        $validatedDepartmentIds === []
    ) {
        $error = 'Alle Felder sind Pflichtfelder. Bitte alles ausfüllen und mindestens einen Standort/Abteilung wählen.';
    } elseif (!in_array($status, allowed_contract_statuses(), true)) {
        $error = 'Ungültiger Status.';
    } elseif (!isset($_FILES['contract_file']) || (int)$_FILES['contract_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Bitte beim Anlegen direkt ein Dokument hochladen.';
    } else {
        $contract_end = date('Y-m-d', strtotime($contract_start . " +$duration_months months"));
        $contractId = 0;
        $targetPath = '';
        $db->begin_transaction();

        try {
            $stmt = db_prepare($db, "
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
            ", 'contract_create_insert_contract');

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

            if (!$stmt->execute()) {
                throw new RuntimeException('Contract insert failed');
            }

            $contractId = (int)$stmt->insert_id;
            $stmt->close();

            $stmtLoc = db_prepare($db, "
                INSERT INTO contract_locations (contract_id, location_id)
                VALUES (?, ?)
            ", 'contract_create_insert_locations');

            foreach ($validatedLocationIds as $locId) {
                $stmtLoc->bind_param('ii', $contractId, $locId);
                $stmtLoc->execute();
            }
            $stmtLoc->close();

            $stmtDept = db_prepare($db, "
                INSERT INTO contract_departments (contract_id, department_id)
                VALUES (?, ?)
            ", 'contract_create_insert_departments');

            foreach ($validatedDepartmentIds as $deptId) {
                $stmtDept->bind_param('ii', $contractId, $deptId);
                $stmtDept->execute();
            }
            $stmtDept->close();

            $uploadBasePath = CONTRACT_UPLOAD_BASE_PATH;
            $safeSupplier = preg_replace('/[^A-Za-z0-9_-]/', '_', $supplier) ?: 'vertrag';
            $contractFolder = $uploadBasePath . '/' . $contractId . '_' . $safeSupplier;

            if (!is_dir($contractFolder) && !mkdir($contractFolder, 0775, true) && !is_dir($contractFolder)) {
                throw new RuntimeException('Upload folder create failed');
            }

            $fileError = (int)$_FILES['contract_file']['error'];
            $tmpName = (string)$_FILES['contract_file']['tmp_name'];
            $originalName = (string)$_FILES['contract_file']['name'];
            $fileSize = (int)$_FILES['contract_file']['size'];

            if ($fileError !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload error');
            }
            if ($fileSize <= 0 || $fileSize > CONTRACT_MAX_UPLOAD_BYTES) {
                throw new RuntimeException('Upload size invalid');
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExt = ['pdf', 'doc', 'docx'];
            if (!in_array($ext, $allowedExt, true)) {
                throw new RuntimeException('Upload extension invalid');
            }

            $allowedMimeByExt = [
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            ];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = is_resource($finfo) ? (string)finfo_file($finfo, $tmpName) : '';
            if (is_resource($finfo)) {
                finfo_close($finfo);
            }
            if (!in_array($mime, $allowedMimeByExt[$ext], true)) {
                throw new RuntimeException('Upload mime invalid: ' . $mime);
            }

            $timestamp = date('Ymd_His');
            $targetFileName = $safeSupplier . '_' . $timestamp . '.' . $ext;
            $targetPath = $contractFolder . '/' . $targetFileName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                throw new RuntimeException('Upload move failed');
            }

            $stmtFile = db_prepare($db, "
                INSERT INTO contract_files (contract_id, file_name, file_path)
                VALUES (?, ?, ?)
            ", 'contract_create_insert_file');

            $stmtFile->bind_param('iss', $contractId, $targetFileName, $targetPath);
            $stmtFile->execute();
            $stmtFile->close();

            $db->commit();
            $success = 'Vertrag inklusive Dokument wurde gespeichert.';
        } catch (Throwable $e) {
            $db->rollback();
            if ($targetPath !== '' && is_file($targetPath)) {
                @unlink($targetPath);
            }
            app_log('contract_create_txn', $e->getMessage());
            $error = 'Daten konnten nicht vollständig gespeichert werden. Es wurde nichts übernommen.';
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

<form method="post" enctype="multipart/form-data">
    <?php echo csrf_input(); ?>

    <label for="supplier">Lieferant *</label><br>
    <input type="text" id="supplier" name="supplier" required><br><br>

    <label for="contract_subject">Vertragsgegenstand *</label><br>
    <textarea id="contract_subject" name="contract_subject" rows="4" cols="60" required></textarea><br><br>

    <label for="contract_start">Vertragsbeginn *</label><br>
    <input type="date" id="contract_start" name="contract_start" required><br><br>

    <label for="duration_months">Laufzeit (Monate) *</label><br>
    <input type="number" id="duration_months" name="duration_months" min="1" required><br><br>

    <label for="termination_period_months">Kündigungsfrist (Monate) *</label><br>
    <input type="number" id="termination_period_months" name="termination_period_months" min="0" required><br><br>

    <label for="termination_text">Kündigungsbedingungen *</label><br>
    <textarea id="termination_text" name="termination_text" rows="4" cols="60" required></textarea><br><br>

    <label for="status">Status *</label><br>
    <select id="status" name="status" required>
        <option value="">-- auswählen --</option>
        <option value="active">Aktiv</option>
        <option value="terminated">Gekündigt</option>
        <option value="ended">Beendet</option>
        <option value="adjustment_required">Anpassung erforderlich</option>
        <option value="archived">Archiviert</option>
    </select><br><br>

    <label for="responsible_user_id">Verantwortlicher *</label><br>
    <select id="responsible_user_id" name="responsible_user_id" required>
        <option value="0">-- auswählen --</option>
        <?php foreach ($users as $userItem): ?>
            <option value="<?php echo (int)$userItem['id']; ?>">
                <?php echo htmlspecialchars((string)$userItem['display_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Standorte * (mind. 1)</label><br>
    <div class="check-grid">
        <?php foreach ($locations as $location): ?>
            <label>
                <input type="checkbox" name="location_ids[]" value="<?php echo (int)$location['id']; ?>">
                <?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>
    </div><br>

    <label>Abteilungen * (mind. 1)</label><br>
    <div class="check-grid">
        <?php foreach ($departments as $department): ?>
            <label>
                <input type="checkbox" name="department_ids[]" value="<?php echo (int)$department['id']; ?>">
                <?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>
    </div><br>

    <label for="contract_file">Dokument * (PDF/DOC/DOCX)</label><br>
    <input type="file" id="contract_file" name="contract_file" accept=".pdf,.doc,.docx" required><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>
