<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

$db = db();

$userId = current_user_id();
$userRole = current_user_role();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Ungültige Vertrags-ID.');
}

require_contract_access($db, $userId, $userRole, $id);

$uploadBasePath = 'C:/Vertragsdaten/Uploads';

$error = '';
$success = '';

$stmt = $db->prepare("
    SELECT
        id,
        supplier,
        contract_subject,
        contract_start,
        duration_months,
        contract_end,
        termination_period_months,
        termination_text,
        status,
        responsible_user_id
    FROM contracts
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    app_log('db-prepare-vertrag-laden', $db->error);
    app_abort('Datenbank-Fehler.', 500);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$contract = $result->fetch_assoc();
$stmt->close();

if (!$contract) {
    die('Vertrag nicht gefunden.');
}

$users = get_active_users($db);
$locations = get_allowed_locations($db, $userId, $userRole);
$departments = get_allowed_departments($db, $userId, $userRole);

$allowedLocationIds = get_allowed_ids($locations);
$allowedDepartmentIds = get_allowed_ids($departments);

$selectedLocations = get_contract_location_ids($db, $id);
$selectedDepartments = get_contract_department_ids($db, $id);

if ($userRole !== 'admin') {
    foreach ($selectedLocations as $locationId) {
        if (!in_array($locationId, $allowedLocationIds, true)) {
            die('Zugriff verweigert.');
        }
    }

    foreach ($selectedDepartments as $departmentId) {
        if (!in_array($departmentId, $allowedDepartmentIds, true)) {
            die('Zugriff verweigert.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    if (!can_manage_contracts()) {
        die('Zugriff verweigert.');
    }

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
    } else {
        $contract_end = date('Y-m-d', strtotime($contract_start . " +$duration_months months"));

        $stmt = $db->prepare("
            UPDATE contracts
            SET
                supplier = ?,
                contract_subject = ?,
                contract_start = ?,
                duration_months = ?,
                contract_end = ?,
                termination_period_months = ?,
                termination_text = ?,
                status = ?,
                responsible_user_id = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $stmt->bind_param(
            'sssissssii',
            $supplier,
            $contract_subject,
            $contract_start,
            $duration_months,
            $contract_end,
            $termination_period_months,
            $termination_text,
            $status,
            $responsible_user_id,
            $id
        );

        if (!$stmt->execute()) {
            app_log('db-execute', $stmt->error);
                $error = 'Daten konnten nicht gespeichert werden.';
        }

        $stmt->close();

        if ($error === '') {
            $stmt = $db->prepare("DELETE FROM contract_locations WHERE contract_id = ?");
            if (!$stmt) {
                app_log('db-prepare-standorte-loeschen', $db->error);
                app_abort('Datenbank-Fehler.', 500);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("DELETE FROM contract_departments WHERE contract_id = ?");
            if (!$stmt) {
                app_log('db-prepare-abteilungen-loeschen', $db->error);
                app_abort('Datenbank-Fehler.', 500);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            if (!empty($validatedLocationIds)) {
                $stmt = $db->prepare("
                    INSERT INTO contract_locations (contract_id, location_id)
                    VALUES (?, ?)
                ");

                if (!$stmt) {
                    app_log('db-prepare-standorte-speichern', $db->error);
                    app_abort('Datenbank-Fehler.', 500);
                }

                foreach ($validatedLocationIds as $locationId) {
                    $stmt->bind_param('ii', $id, $locationId);
                    $stmt->execute();
                }

                $stmt->close();
            }

            if (!empty($validatedDepartmentIds)) {
                $stmt = $db->prepare("
                    INSERT INTO contract_departments (contract_id, department_id)
                    VALUES (?, ?)
                ");

                if (!$stmt) {
                    app_log('db-prepare-abteilungen-speichern', $db->error);
                    app_abort('Datenbank-Fehler.', 500);
                }

                foreach ($validatedDepartmentIds as $departmentId) {
                    $stmt->bind_param('ii', $id, $departmentId);
                    $stmt->execute();
                }

                $stmt->close();
            }

            if (isset($_FILES['pdf_files']) && is_array($_FILES['pdf_files']['name'])) {
                $safeSupplier = preg_replace('/[^A-Za-z0-9_-]/', '_', $supplier);
                $contractFolder = $uploadBasePath . '/' . $id . '_' . $safeSupplier;

                if (!is_dir($contractFolder)) {
                    if (!mkdir($contractFolder, 0775, true) && !is_dir($contractFolder)) {
                        die('Upload-Ordner konnte nicht erstellt werden.');
                    }
                }

                $fileCount = count($_FILES['pdf_files']['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    $fileError = (int)$_FILES['pdf_files']['error'][$i];
                    $tmpName = (string)$_FILES['pdf_files']['tmp_name'][$i];
                    $originalName = (string)$_FILES['pdf_files']['name'][$i];
                    $fileSize = (int)$_FILES['pdf_files']['size'][$i];

                    if ($fileError === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($fileError !== UPLOAD_ERR_OK) {
                        $error = 'Fehler beim Datei-Upload.';
                        continue;
                    }

                    if ($fileSize > 20 * 1024 * 1024) {
                        $error = 'Eine Datei ist größer als 20 MB.';
                        continue;
                    }

                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if ($extension !== 'pdf') {
                        $error = 'Nur PDF-Dateien sind erlaubt.';
                        continue;
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    if ($mimeType !== 'application/pdf') {
                        $error = 'Ungültiger Dateityp erkannt.';
                        continue;
                    }

                    $timestamp = date('Ymd_His');
                    $targetFileName = $safeSupplier . '_' . $timestamp . '_' . $i . '.pdf';
                    $targetPath = $contractFolder . '/' . $targetFileName;

                    if (!move_uploaded_file($tmpName, $targetPath)) {
                        $error = 'Datei konnte nicht gespeichert werden.';
                        continue;
                    }

                    if (!file_exists($targetPath)) {
                        $error = 'Datei wurde nicht korrekt gespeichert.';
                        continue;
                    }

                    $stmt = $db->prepare("
                        INSERT INTO contract_files (contract_id, file_name, file_path)
                        VALUES (?, ?, ?)
                    ");

                    if (!$stmt) {
                        app_log('db-prepare-datei-speichern', $db->error);
                        app_abort('Datenbank-Fehler.', 500);
                    }

                    $stmt->bind_param('iss', $id, $targetFileName, $targetPath);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            if ($error === '') {
                $success = 'Vertrag wurde gespeichert.';

                $stmt = $db->prepare("
                    SELECT
                        id,
                        supplier,
                        contract_subject,
                        contract_start,
                        duration_months,
                        contract_end,
                        termination_period_months,
                        termination_text,
                        status,
                        responsible_user_id
                    FROM contracts
                    WHERE id = ?
                    LIMIT 1
                ");

                if (!$stmt) {
                    app_log('db-prepare-vertrag-neu-laden', $db->error);
                    app_abort('Datenbank-Fehler.', 500);
                }

                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $contract = $result->fetch_assoc();
                $stmt->close();

                $selectedLocations = $validatedLocationIds;
                $selectedDepartments = $validatedDepartmentIds;
            }
        }
    }
}

$filesStmt = $db->prepare("
    SELECT id, file_name, file_path, uploaded_at
    FROM contract_files
    WHERE contract_id = ?
    ORDER BY uploaded_at DESC, id DESC
");

if (!$filesStmt) {
    app_log('db-prepare-dateien-laden', $db->error);
    app_abort('Datenbank-Fehler.', 500);
}

$filesStmt->bind_param('i', $id);
$filesStmt->execute();
$filesResult = $filesStmt->get_result();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <title>Vertrag bearbeiten</title>
</head>
<body>

<h1>Vertrag bearbeiten</h1>

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

    <label for="supplier">Lieferant</label><br>
    <input
        type="text"
        id="supplier"
        name="supplier"
        required
        value="<?php echo htmlspecialchars((string)$contract['supplier'], ENT_QUOTES, 'UTF-8'); ?>"
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><br><br>

    <label for="contract_subject">Vertragsgegenstand *</label><br>
    <textarea
        id="contract_subject"
        name="contract_subject"
        rows="4"
        cols="60"
        required
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><?php echo htmlspecialchars((string)$contract['contract_subject'], ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    <label for="contract_start">Vertragsbeginn</label><br>
    <input
        type="date"
        id="contract_start"
        name="contract_start"
        required
        value="<?php echo htmlspecialchars((string)$contract['contract_start'], ENT_QUOTES, 'UTF-8'); ?>"
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><br><br>

    <label for="duration_months">Laufzeit (Monate)</label><br>
    <input
        type="number"
        id="duration_months"
        name="duration_months"
        min="1"
        required
        value="<?php echo (int)$contract['duration_months']; ?>"
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><br><br>

    <label for="termination_period_months">Kündigungsfrist (Monate) *</label><br>
    <input
        type="number"
        id="termination_period_months"
        name="termination_period_months"
        min="0"
        value="<?php echo (int)$contract['termination_period_months']; ?>"
        required
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><br><br>

    <label for="termination_text">Kündigungsbedingungen *</label><br>
    <textarea
        id="termination_text"
        name="termination_text"
        rows="4"
        cols="60"
        required
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><?php echo htmlspecialchars((string)$contract['termination_text'], ENT_QUOTES, 'UTF-8'); ?></textarea><br><br>

    <label for="status">Status *</label><br>
    <select id="status" name="status" required <?php echo can_manage_contracts() ? '' : 'disabled'; ?>>
        <option value="active" <?php echo $contract['status'] === 'active' ? 'selected' : ''; ?>>Aktiv</option>
        <option value="terminated" <?php echo $contract['status'] === 'terminated' ? 'selected' : ''; ?>>Gekündigt</option>
        <option value="ended" <?php echo $contract['status'] === 'ended' ? 'selected' : ''; ?>>Beendet</option>
        <option value="adjustment_required" <?php echo $contract['status'] === 'adjustment_required' ? 'selected' : ''; ?>>Anpassung erforderlich</option>
        <option value="archived" <?php echo $contract['status'] === 'archived' ? 'selected' : ''; ?>>Archiviert</option>
    </select><br><br>

    <label for="responsible_user_id">Verantwortlicher *</label><br>
    <select id="responsible_user_id" name="responsible_user_id" required <?php echo can_manage_contracts() ? '' : 'disabled'; ?>>
        <option value="0">-- auswählen --</option>
        <?php foreach ($users as $userItem): ?>
            <option
                value="<?php echo (int)$userItem['id']; ?>"
                <?php echo ((int)$contract['responsible_user_id'] === (int)$userItem['id']) ? 'selected' : ''; ?>
            >
                <?php echo htmlspecialchars((string)$userItem['display_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Standorte * (mind. 1)</label><br>
    <div class="check-grid">
        <?php foreach ($locations as $location): ?>
            <label>
                <input
                    type="checkbox"
                    name="location_ids[]"
                    value="<?php echo (int)$location['id']; ?>"
                    <?php echo in_array((int)$location['id'], $selectedLocations, true) ? 'checked' : ''; ?>
                    <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
                >
                <?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>
    </div><br>

    <label>Abteilungen * (mind. 1)</label><br>
    <div class="check-grid">
        <?php foreach ($departments as $department): ?>
            <label>
                <input
                    type="checkbox"
                    name="department_ids[]"
                    value="<?php echo (int)$department['id']; ?>"
                    <?php echo in_array((int)$department['id'], $selectedDepartments, true) ? 'checked' : ''; ?>
                    <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
                >
                <?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>
            </label>
        <?php endforeach; ?>
    </div><br>

    <p>
        Berechnetes Vertragsende:
        <strong><?php echo htmlspecialchars((string)$contract['contract_end'], ENT_QUOTES, 'UTF-8'); ?></strong>
    </p>

    <label for="pdf_files">PDF-Dateien hochladen</label><br>
    <input
        type="file"
        id="pdf_files"
        name="pdf_files[]"
        accept=".pdf,application/pdf"
        multiple
        <?php echo can_manage_contracts() ? '' : 'disabled'; ?>
    ><br><br>

    <?php if (can_manage_contracts()): ?>
        <button type="submit">Speichern</button>
    <?php endif; ?>
</form>

<h2>Vorhandene Dateien</h2>

<?php if ($filesResult->num_rows === 0): ?>
    <p>Keine Dateien vorhanden.</p>
<?php else: ?>
    <ul>
        <?php while ($fileRow = $filesResult->fetch_assoc()): ?>
            <li>
                <a href="/file_download.php?id=<?php echo (int)$fileRow['id']; ?>" target="_blank">
                    <?php echo htmlspecialchars((string)$fileRow['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                -
                <?php echo htmlspecialchars((string)$fileRow['uploaded_at'], ENT_QUOTES, 'UTF-8'); ?>
            </li>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>

</body>
</html>
