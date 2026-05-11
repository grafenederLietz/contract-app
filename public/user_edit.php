<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

require_login();

$currentUser = current_user();

if (($currentUser['role'] ?? '') !== 'admin') {
    app_abort('Zugriff verweigert.', 403);
}

$db = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    app_abort('Ungültige Benutzer-ID.', 400);
}

$error = '';
$success = '';

$stmt = $db->prepare("
    SELECT id, username, display_name, email, role, is_active
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$editUser = $result->fetch_assoc();
$stmt->close();

if (!$editUser) {
    app_abort('Benutzer nicht gefunden.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $username = trim((string)($_POST['username'] ?? ''));
    $display_name = trim((string)($_POST['display_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = trim((string)($_POST['role'] ?? 'viewer'));
    $password = (string)($_POST['password'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $department_ids = $_POST['department_ids'] ?? [];
    $location_ids = $_POST['location_ids'] ?? [];

    $allowedRoles = ['admin', 'editor', 'viewer'];

    if ($username === '' || $display_name === '') {
        $error = 'Bitte Pflichtfelder ausfüllen.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Ungültige Rolle.';
    } else {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        if (!$checkStmt) {
            app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
        }

        $checkStmt->bind_param('si', $username, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingUser = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            $error = 'Benutzername existiert bereits.';
        } else {
            if ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $updateStmt = $db->prepare("
                    UPDATE users
                    SET username = ?, display_name = ?, email = ?, role = ?, is_active = ?, password_hash = ?
                    WHERE id = ?
                ");

                if (!$updateStmt) {
                    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                }

                $updateStmt->bind_param(
                    'ssssisi',
                    $username,
                    $display_name,
                    $email,
                    $role,
                    $is_active,
                    $password_hash,
                    $id
                );
            } else {
                $updateStmt = $db->prepare("
                    UPDATE users
                    SET username = ?, display_name = ?, email = ?, role = ?, is_active = ?
                    WHERE id = ?
                ");

                if (!$updateStmt) {
                    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                }

                $updateStmt->bind_param(
                    'ssssii',
                    $username,
                    $display_name,
                    $email,
                    $role,
                    $is_active,
                    $id
                );
            }

            if ($updateStmt->execute()) {
                $updateStmt->close();

                $deleteDepartmentsStmt = $db->prepare("DELETE FROM user_departments WHERE user_id = ?");
                if (!$deleteDepartmentsStmt) {
                    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                }

                $deleteDepartmentsStmt->bind_param('i', $id);
                $deleteDepartmentsStmt->execute();
                $deleteDepartmentsStmt->close();

                if (!empty($department_ids)) {
                    $insertDepartmentStmt = $db->prepare("
                        INSERT INTO user_departments (user_id, department_id)
                        VALUES (?, ?)
                    ");

                    if (!$insertDepartmentStmt) {
                        app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                    }

                    foreach ($department_ids as $departmentId) {
                        $departmentId = (int)$departmentId;

                        if ($departmentId > 0) {
                            $insertDepartmentStmt->bind_param('ii', $id, $departmentId);
                            $insertDepartmentStmt->execute();
                        }
                    }

                    $insertDepartmentStmt->close();
                }

                $deleteLocationsStmt = $db->prepare("DELETE FROM user_locations WHERE user_id = ?");
                if (!$deleteLocationsStmt) {
                    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                }

                $deleteLocationsStmt->bind_param('i', $id);
                $deleteLocationsStmt->execute();
                $deleteLocationsStmt->close();

                if (!empty($location_ids)) {
                    $insertLocationStmt = $db->prepare("
                        INSERT INTO user_locations (user_id, location_id)
                        VALUES (?, ?)
                    ");

                    if (!$insertLocationStmt) {
                        app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
                    }

                    foreach ($location_ids as $locationId) {
                        $locationId = (int)$locationId;

                        if ($locationId > 0) {
                            $insertLocationStmt->bind_param('ii', $id, $locationId);
                            $insertLocationStmt->execute();
                        }
                    }

                    $insertLocationStmt->close();
                }

                $success = 'Benutzer wurde gespeichert.';
            } else {
                app_log('db-execute', $updateStmt->error);
                $error = 'Daten konnten nicht gespeichert werden.';
                $updateStmt->close();
            }

            $reloadStmt = $db->prepare("
                SELECT id, username, display_name, email, role, is_active
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $reloadStmt->bind_param('i', $id);
            $reloadStmt->execute();
            $reloadResult = $reloadStmt->get_result();
            $editUser = $reloadResult->fetch_assoc();
            $reloadStmt->close();
        }
    }
}

$selectedDepartments = [];

$selectedDepartmentsStmt = $db->prepare("
    SELECT department_id
    FROM user_departments
    WHERE user_id = ?
");

if (!$selectedDepartmentsStmt) {
    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
}

$selectedDepartmentsStmt->bind_param('i', $id);
$selectedDepartmentsStmt->execute();
$selectedDepartmentsResult = $selectedDepartmentsStmt->get_result();

while ($row = $selectedDepartmentsResult->fetch_assoc()) {
    $selectedDepartments[] = (int)$row['department_id'];
}

$selectedDepartmentsStmt->close();

$selectedLocations = [];

$selectedLocationsStmt = $db->prepare("
    SELECT location_id
    FROM user_locations
    WHERE user_id = ?
");

if (!$selectedLocationsStmt) {
    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
}

$selectedLocationsStmt->bind_param('i', $id);
$selectedLocationsStmt->execute();
$selectedLocationsResult = $selectedLocationsStmt->get_result();

while ($row = $selectedLocationsResult->fetch_assoc()) {
    $selectedLocations[] = (int)$row['location_id'];
}

$selectedLocationsStmt->close();

$departments = $db->query("
    SELECT id, name
    FROM departments
    ORDER BY name ASC
");

if (!$departments) {
    app_log('user_edit_load_departments', $db->error);
    app_abort('Datenbank-Fehler.', 500);
}

$locations = $db->query("
    SELECT id, name
    FROM locations
    ORDER BY name ASC
");

if (!$locations) {
    app_log('user_edit_load_locations', $db->error);
    app_abort('Datenbank-Fehler.', 500);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/app.css">
    <title>Benutzer bearbeiten</title>
</head>
<body>

<h1>Benutzer bearbeiten</h1>

<p>
    <a href="/dashboard.php">Dashboard</a> |
    <a href="/users.php">Benutzerverwaltung</a> |
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

    <label for="username">Benutzername</label><br>
    <input
        type="text"
        id="username"
        name="username"
        required
        value="<?php echo htmlspecialchars((string)$editUser['username'], ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <label for="display_name">Anzeigename</label><br>
    <input
        type="text"
        id="display_name"
        name="display_name"
        required
        value="<?php echo htmlspecialchars((string)$editUser['display_name'], ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <label for="email">E-Mail</label><br>
    <input
        type="email"
        id="email"
        name="email"
        value="<?php echo htmlspecialchars((string)($editUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
    ><br><br>

    <label for="role">Rolle</label><br>
    <select id="role" name="role">
        <option value="admin" <?php echo $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="editor" <?php echo $editUser['role'] === 'editor' ? 'selected' : ''; ?>>Bearbeiter</option>
        <option value="viewer" <?php echo $editUser['role'] === 'viewer' ? 'selected' : ''; ?>>Leser</option>
    </select><br><br>

    <label for="department_ids">Abteilungen</label><br>
    <select id="department_ids" name="department_ids[]" multiple size="6">
        <?php while ($department = $departments->fetch_assoc()): ?>
            <option
                value="<?php echo (int)$department['id']; ?>"
                <?php echo in_array((int)$department['id'], $selectedDepartments, true) ? 'selected' : ''; ?>
            >
                <?php echo htmlspecialchars((string)$department['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="location_ids">Standorte</label><br>
    <select id="location_ids" name="location_ids[]" multiple size="6">
        <?php while ($location = $locations->fetch_assoc()): ?>
            <option
                value="<?php echo (int)$location['id']; ?>"
                <?php echo in_array((int)$location['id'], $selectedLocations, true) ? 'selected' : ''; ?>
            >
                <?php echo htmlspecialchars((string)$location['name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="password">Neues Passwort (leer lassen = unverändert)</label><br>
    <input type="password" id="password" name="password"><br><br>

    <label>
        <input type="checkbox" name="is_active" <?php echo (int)$editUser['is_active'] === 1 ? 'checked' : ''; ?>>
        Aktiv
    </label><br><br>

    <button type="submit">Speichern</button>
</form>

</body>
</html>