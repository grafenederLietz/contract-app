<?php

function app_log($context, $details = '') {
    error_log('[contract-app][' . $context . '] ' . $details);
}

function app_abort($message = 'Interner Fehler.', $statusCode = 500) {
    if (!headers_sent()) {
        http_response_code((int)$statusCode);
    }
    exit($message);
}

function load_local_config() {
    static $cached = null;

    if (is_array($cached)) {
        return $cached;
    }

    $localConfigFile = dirname(__FILE__) . '/local.php';
    if (!is_file($localConfigFile)) {
        app_log('config', 'config/local.php fehlt.');
        $cached = array();
        return $cached;
    }

    $data = require $localConfigFile;
    if (!is_array($data)) {
        app_log('config', 'config/local.php ist kein Array.');
        $cached = array();
        return $cached;
    }

    $cached = $data;
    return $cached;
}

function db() {
    static $mysqli = null;

    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }

    $local = load_local_config();

    $dbHost = '';
    if (isset($local['db_host'])) {
        $dbHost = trim((string)$local['db_host']);
    }

    $dbName = '';
    if (isset($local['db_name'])) {
        $dbName = trim((string)$local['db_name']);
    }

    $dbUser = '';
    if (isset($local['db_user'])) {
        $dbUser = trim((string)$local['db_user']);
    }

    $dbPass = '';
    if (isset($local['db_pass'])) {
        $dbPass = trim((string)$local['db_pass']);
    }

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPass === '') {
        app_log('config', 'DB-Konfiguration unvollständig: config/local.php muss db_host, db_name, db_user und db_pass enthalten.');
        app_abort('Konfigurationsfehler.', 500);
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

function db_prepare($db, $sql, $context) {
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        app_log($context, $db->error);
        app_abort('Datenbank-Fehler.', 500);
    }

    return $stmt;
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$logDir = dirname(__FILE__) . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

$isHttps = false;
if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
    $isHttps = true;
}

session_name('CONTRACTAPPSESSID');
session_set_cookie_params(0, '/', '', $isHttps, true);

if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} elseif (session_id() === '') {
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

if (!defined('CONTRACT_UPLOAD_BASE_PATH')) {
    define('CONTRACT_UPLOAD_BASE_PATH', 'C:/Vertragsdaten/Uploads');
}
if (!defined('CONTRACT_MAX_UPLOAD_BYTES')) {
    define('CONTRACT_MAX_UPLOAD_BYTES', 20 * 1024 * 1024);
}
