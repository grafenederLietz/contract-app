param(
    [string]$AppRoot = "C:\WebApps\contract-app",
    [string]$Branch = "main",
    [string]$PhpExe = "",
    [switch]$Force
)

$ErrorActionPreference = 'Stop'

function Write-Section($Title) {
    Write-Host ""
    Write-Host "=== $Title ==="
}

function Run-Cmd($CommandLine) {
    Write-Host "> $CommandLine"
    cmd /c $CommandLine
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed with exit code ${LASTEXITCODE}: $CommandLine"
    }
}

Write-Section "Sicherheitscheck"
Write-Host "AppRoot: $AppRoot"
Write-Host "Branch:  $Branch"

if (-not (Test-Path $AppRoot)) {
    throw "AppRoot existiert nicht: $AppRoot"
}

Set-Location $AppRoot

$repoRoot = (git rev-parse --show-toplevel 2>$null)
if ($LASTEXITCODE -ne 0) {
    throw "AppRoot ist kein Git-Repository: $AppRoot"
}

$repoRoot = $repoRoot.Trim()
if ($repoRoot -replace '/', '\' -ne $AppRoot.TrimEnd('\')) {
    Write-Host "WARNUNG: Git-Root ist '$repoRoot', AppRoot ist '$AppRoot'."
}

Write-Section "Aktueller Zustand"
Run-Cmd "git status --short"
Run-Cmd "git log -1 --oneline"

$configPath = Join-Path $AppRoot "config\config.php"
$localPath = Join-Path $AppRoot "config\local.php"
$examplePath = Join-Path $AppRoot "config\local.php.example"

if (Test-Path $localPath) {
    $backupPath = Join-Path $env:TEMP ("contract-app-local.php.backup-{0:yyyyMMdd-HHmmss}" -f (Get-Date))
    Copy-Item $localPath $backupPath -Force
    Write-Host "config/local.php wurde ausserhalb des Repos gesichert nach: $backupPath"
} else {
    Write-Host "WARNUNG: config/local.php fehlt aktuell. Nach dem Reset muss diese Datei erstellt/befuellt werden."
}

if (-not $Force) {
    Write-Host ""
    Write-Host "Dieser Vorgang setzt alle getrackten Dateien hart auf origin/$Branch zurueck."
    Write-Host "Nicht getrackte/ignorierte config/local.php bleibt erhalten; falls vorhanden wurde sie gesichert."
    Write-Host "Starte erneut mit -Force, um den Reset auszufuehren."
    exit 2
}

Write-Section "Repository reparieren"
Run-Cmd "git fetch origin"
Run-Cmd "git checkout $Branch"
Run-Cmd "git reset --hard origin/$Branch"
Run-Cmd "git clean -fd"

Write-Section "Lokale DB-Konfiguration"
if (-not (Test-Path $localPath)) {
    if (Test-Path $examplePath) {
        Copy-Item $examplePath $localPath
        Write-Host "config/local.php wurde aus config/local.php.example erstellt."
        Write-Host "WICHTIG: Bitte jetzt db_host, db_name, db_user und db_pass in config/local.php eintragen."
    } else {
        Write-Host "FEHLER: config/local.php fehlt und config/local.php.example wurde nicht gefunden."
    }
} else {
    Write-Host "config/local.php ist vorhanden (Inhalt wird nicht ausgegeben)."
}

Write-Section "Validierung"
Run-Cmd "git status --short"

if ($PhpExe -ne '') {
    Run-Cmd "`"$PhpExe`" -l config\config.php"
    Run-Cmd "`"$PhpExe`" scripts\verify_config.php"
} else {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($null -ne $phpCommand) {
        Run-Cmd "php -l config\config.php"
        Run-Cmd "php scripts\verify_config.php"
    } else {
        Write-Host "Hinweis: php ist nicht im PATH. Optional mit -PhpExe C:\Pfad\zu\php.exe erneut validieren."
    }
}

if (Test-Path $configPath) {
    Get-FileHash $configPath -Algorithm SHA256 | Format-List
}

Write-Section "Fertig"
Write-Host "Wenn IIS danach noch einen Parse Error zeigt, pruefe das IIS/FastCGI Handler Mapping auf den tatsaechlichen php-cgi.exe Pfad."
