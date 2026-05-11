param(
    [string]$AppRoot = "C:\WebApps\contract-app",
    [int]$ReportedLine = 84
)

$ErrorActionPreference = 'Continue'

function Write-Section($Title) {
    Write-Host ""
    Write-Host "=== $Title ==="
}

function Show-CommandResult($CommandLine) {
    Write-Host "> $CommandLine"
    cmd /c $CommandLine
    Write-Host "ExitCode: $LASTEXITCODE"
}

Write-Section "Basis"
Write-Host "AppRoot: $AppRoot"
Write-Host "Computer: $env:COMPUTERNAME"
Write-Host "User: $env:USERNAME"
Write-Host "Datum: $(Get-Date -Format o)"

if (-not (Test-Path $AppRoot)) {
    Write-Host "FEHLER: AppRoot existiert nicht: $AppRoot"
    exit 1
}

Set-Location $AppRoot

Write-Section "Git-Stand"
Show-CommandResult "git rev-parse --show-toplevel"
Show-CommandResult "git status --short"
Show-CommandResult "git log -1 --oneline"

Write-Section "PHP CLI"
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($null -eq $phpCommand) {
    Write-Host "FEHLER: php wurde im PATH nicht gefunden. Bitte den PHP-Pfad aus dem IIS/FastCGI-Handler pruefen."
} else {
    Write-Host "php im PATH: $($phpCommand.Source)"
    Show-CommandResult "php -v"
}

Write-Section "Config-Dateien"
$configPath = Join-Path $AppRoot "config\config.php"
$localPath = Join-Path $AppRoot "config\local.php"
Write-Host "config.php vorhanden: $(Test-Path $configPath)"
Write-Host "local.php vorhanden:  $(Test-Path $localPath)"

if (Test-Path $configPath) {
    Get-Item $configPath | Select-Object FullName, Length, LastWriteTime | Format-List
    Get-FileHash $configPath -Algorithm SHA256 | Format-List
}

if (Test-Path $localPath) {
    Get-Item $localPath | Select-Object FullName, Length, LastWriteTime | Format-List
    Write-Host "Hinweis: Inhalt von local.php wird absichtlich NICHT ausgegeben, damit keine Credentials in Logs landen."
}

Write-Section "PHP Syntax / Repo-Pruefung"
Show-CommandResult "php -l config\config.php"
Show-CommandResult "php -l config\local.php"
Show-CommandResult "php scripts\verify_config.php"

Write-Section "Ausschnitt config.php um gemeldete Zeile"
if (Test-Path $configPath) {
    $start = [Math]::Max(1, $ReportedLine - 10)
    $end = $ReportedLine + 10
    $lineNumber = 0
    Get-Content $configPath | ForEach-Object {
        $lineNumber++
        if ($lineNumber -ge $start -and $lineNumber -le $end) {
            "{0,4}: {1}" -f $lineNumber, $_
        }
    }
}

Write-Section "Suche nach bekannten kaputten Fragmenten"
$patterns = @(
    "function app_log(string",
    "function app_abort(string",
    "function load_local_config(): array",
    "function db(): mysqli",
    "function db_prepare(mysqli",
    "Schritt 1 Stabilitaet",
    "jREIOV0jkO6Q5dN23OYV",
    "CONTRACTAPP_DB_"
)
foreach ($pattern in $patterns) {
    $matches = Select-String -Path $configPath -Pattern $pattern -SimpleMatch -ErrorAction SilentlyContinue
    if ($matches) {
        Write-Host "TREFFER: $pattern"
        $matches | ForEach-Object { Write-Host ("  Zeile {0}: {1}" -f $_.LineNumber, $_.Line.Trim()) }
    } else {
        Write-Host "OK: kein Treffer fuer '$pattern'"
    }
}

Write-Section "Naechste Schritte"
Write-Host "Wenn php -l hier OK ist, aber IIS weiter Parse Errors zeigt, nutzt IIS sehr wahrscheinlich einen anderen PHP-Interpreter, eine andere Datei oder einen Cache/Opcode-Cache."
Write-Host "Dann bitte die Ausgabe dieses Skripts bereitstellen und zusaetzlich den IIS FastCGI/Handler-Mapping PHP-Pfad pruefen."
