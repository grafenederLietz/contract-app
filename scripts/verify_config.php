<?php

$rootDir = dirname(__DIR__);
$configFile = $rootDir . '/config/config.php';

if (!is_file($configFile)) {
    fwrite(STDERR, "Fehler: config/config.php wurde nicht gefunden.\n");
    exit(1);
}

$contents = file_get_contents($configFile);
if ($contents === false) {
    fwrite(STDERR, "Fehler: config/config.php konnte nicht gelesen werden.\n");
    exit(1);
}

$errors = array();

$forbiddenFragments = array(
    'function app_log(string' => 'Alte typisierte app_log()-Deklaration gefunden.',
    'function app_abort(string' => 'Alte typisierte app_abort()-Deklaration gefunden.',
    'function load_local_config(): array' => 'Alte typisierte load_local_config()-Deklaration gefunden.',
    'function db(): mysqli' => 'Alte typisierte db()-Deklaration gefunden.',
    'function db_prepare(mysqli' => 'Alte typisierte db_prepare()-Deklaration gefunden.',
    'Schritt 1 Stabilität' => 'Alter Legacy-Fallback-Kommentar gefunden.',
    'jREIOV0jkO6Q5dN23OYV' => 'Verbotenes Klartext-Passwort gefunden.',
    "getenv('CONTRACTAPP_DB_" => 'ENV-DB-Credential-Zugriff gefunden; erlaubt ist nur config/local.php.',
    'config_value(' => 'Veralteter config_value()-Fallback gefunden.',
);

foreach ($forbiddenFragments as $fragment => $message) {
    if (strpos($contents, $fragment) !== false) {
        $errors[] = $message;
    }
}

if (strpos($contents, '\\n') !== false) {
    $errors[] = 'Literal \\n gefunden; die Datei wurde vermutlich falsch kopiert.';
}

if (preg_match('/\$dbPass\s*=\s*[\'\"][^\'\"]+[\'\"]\s*;/', $contents)) {
    $errors[] = 'Klartext-Zuweisung an $dbPass in config/config.php gefunden.';
}

$functionCounts = array();
$lines = preg_split('/\r\n|\r|\n/', $contents);
foreach ($lines as $index => $line) {
    if (preg_match('/^function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $line, $matches)) {
        $functionName = $matches[1];
        if (!isset($functionCounts[$functionName])) {
            $functionCounts[$functionName] = 0;
        }
        $functionCounts[$functionName]++;

        if (!preg_match('/\{\s*$/', $line)) {
            $errors[] = 'Funktionsdeklaration ohne öffnende Klammer in derselben Zeile: Zeile ' . ($index + 1) . ' (' . trim($line) . ')';
        }
    }
}

foreach ($functionCounts as $functionName => $count) {
    if ($count > 1) {
        $errors[] = 'Funktion mehrfach definiert: ' . $functionName . ' (' . $count . 'x).';
    }
}

$requiredFunctions = array('app_log', 'app_abort', 'load_local_config', 'db', 'db_prepare');
foreach ($requiredFunctions as $functionName) {
    if (!isset($functionCounts[$functionName])) {
        $errors[] = 'Erforderliche Funktion fehlt: ' . $functionName . '().';
    }
}

if ($errors !== array()) {
    fwrite(STDERR, "config/config.php ist nicht sauber:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "OK: config/config.php ist sauber.\n";
