<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/contract_access.php';

require_login();

$db = db();

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id <= 0) {
    die('Ungültige Datei-ID.');
}

require_file_access($db, current_user_id(), current_user_role(), $file_id);

$stmt = $db->prepare("
    SELECT id, contract_id, file_name, file_path
    FROM contract_files
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    app_log('db-prepare', $db->error);
            app_abort('Datenbank-Fehler.', 500);
}

$stmt->bind_param('i', $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    die('Datei nicht gefunden.');
}

$filePath = (string)$file['file_path'];

if (!file_exists($filePath)) {
    die('Datei existiert nicht auf dem Server.');
}

$mime = mime_content_type($filePath) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename((string)$file['file_name']) . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;