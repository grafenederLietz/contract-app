<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';

session_name('CONTRACTAPPSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

define('CONTRACT_UPLOAD_BASE_PATH', 'C:/Vertragsdaten/Uploads');
define('CONTRACT_MAX_UPLOAD_BYTES', 20 * 1024 * 1024);

function load_local_config(): array
{
    static $cached = null;

    if (is_array($cached)) {
        return $cached;
    }

    $localConfigFile = __DIR__ . '/local.php';

    if (!is_file($localConfigFile)) {
        $cached = [];
        return $cached;
    }

    $data = require $localConfigFile;

    if (!is_array($data)) {
        app_log('config', 'config/local.php ist kein Array.');
        $cached = [];
        return $cached;
    }

    $cached = $data;
    return $cached;
}

function app_log(string $context, string $details = ''): void
{
    error_log('[contract-app][' . $context . '] ' . $details);
}

function app_abort(string $message = 'Interner Fehler.', int $statusCode = 500): never
{
    http_response_code($statusCode);
    exit($message);
}

function db(): mysqli
{
    static $mysqli = null;

    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }

    $local = load_local_config();

    $dbHost = getenv('CONTRACTAPP_DB_HOST')
        ?: (isset($local['db_host']) ? (string)$local['db_host'] : '127.0.0.1');
    $dbName = getenv('CONTRACTAPP_DB_NAME')
        ?: (isset($local['db_name']) ? (string)$local['db_name'] : 'contractdb');
    $dbUser = getenv('CONTRACTAPP_DB_USER')
        ?: (isset($local['db_user']) ? (string)$local['db_user'] : 'contractapp_user');

    $dbPass = getenv('CONTRACTAPP_DB_PASS');
    if ((!is_string($dbPass) || $dbPass === '') && isset($local['db_pass'])) {
        $dbPass = (string)$local['db_pass'];
    }

    if (!is_string($dbPass) || $dbPass === '') {
        app_log('config', 'CONTRACTAPP_DB_PASS fehlt (und kein config/local.php db_pass gesetzt).');
        app_abort('Konfigurationsfehler.', 500);
    $dbHost = getenv('CONTRACTAPP_DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('CONTRACTAPP_DB_NAME') ?: 'contractdb';
    $dbUser = getenv('CONTRACTAPP_DB_USER') ?: 'contractapp_user';

    // Schritt 1 Stabilität: ENV bevorzugen, sonst Legacy-Fallback nutzen.
    $dbPass = getenv('CONTRACTAPP_DB_PASS');
    if (!is_string($dbPass) || $dbPass === '') {
        app_log('config', 'CONTRACTAPP_DB_PASS fehlt.');
        app_abort('Konfigurationsfehler.', 500);
        $dbPass = 'jREIOV0jkO6Q5dN23OYV';
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    if ($mysqli->connect_error) {
        app_log('db-connect', $mysqli->connect_error);
        app_abort('Datenbank-Fehler.', 500);
    }

    if (!$mysqli->set_charset('utf8mb4')) {
        app_log('db-charset', $mysqli->error);
        app_abort('Datenbank-Fehler.', 500);
    }

    return $mysqli;
}

function db_prepare(mysqli $db, string $sql, string $context): mysqli_stmt
{
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        app_log($context, $db->error);
        app_abort('Datenbank-Fehler.', 500);
    }

    return $stmt;
}
