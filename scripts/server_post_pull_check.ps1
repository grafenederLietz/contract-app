param(
    [string]$AppRoot = "C:\WebApps\contract-app",
    [string]$PhpExe = "C:\PHP\php.exe"
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

Write-Section "Basis"
Write-Host "AppRoot: $AppRoot"
Write-Host "PhpExe:  $PhpExe"

if (-not (Test-Path $AppRoot)) {
    throw "AppRoot existiert nicht: $AppRoot"
}

if (-not (Test-Path $PhpExe)) {
    throw "PHP wurde nicht gefunden: $PhpExe"
}

Set-Location $AppRoot

Write-Section "Git-Stand"
Run-Cmd "git status --short --branch"
Run-Cmd "git log -1 --oneline"

Write-Section "PHP Syntax Kern-Dateien"
$phpFiles = @(
    "config\config.php",
    "src\upload.php",
    "public\contract_create.php",
    "public\contract_edit.php",
    "public\file_download.php"
)

foreach ($file in $phpFiles) {
    if (-not (Test-Path (Join-Path $AppRoot $file))) {
        throw "Datei fehlt: $file"
    }

    Run-Cmd "`"$PhpExe`" -l `"$file`""
}

Write-Section "Config-Integrität"
Run-Cmd "`"$PhpExe`" scripts\verify_config.php"

Write-Section "Upload-Pfad"
$uploadRoot = "C:\Vertragsdaten\Uploads"
Write-Host "UploadRoot: $uploadRoot"
if (Test-Path $uploadRoot) {
    Get-Item $uploadRoot | Select-Object FullName, Exists, LastWriteTime | Format-List
    Write-Host "ACL-Auszug:"
    (Get-Acl $uploadRoot).Access | Select-Object IdentityReference, FileSystemRights, AccessControlType, IsInherited | Format-Table -AutoSize
} else {
    Write-Host "WARNUNG: UploadRoot existiert nicht. Die App versucht Ordner bei Uploads anzulegen."
}

Write-Section "Fertig"
Write-Host "Wenn alle Checks OK sind, ist der nach git pull geladene Serverstand syntaktisch sauber."
