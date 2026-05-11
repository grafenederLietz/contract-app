param(
    [string]$AppRoot = "C:\WebApps\contract-app"
)

$ErrorActionPreference = 'Stop'

function Test-IsExcludedPath($FullName, $RootPath) {
    $relative = $FullName.Substring($RootPath.Length) -replace '^[\\/]+', ''
    return (
        $relative -like '.git\*' -or
        $relative -like '.git/*' -or
        $relative -like 'vendor\*' -or
        $relative -like 'vendor/*' -or
        $relative -like 'Uploads\*' -or
        $relative -like 'Uploads/*' -or
        $relative -like 'logs\*' -or
        $relative -like 'logs/*'
    )
}

Write-Host "=== Konfliktmarker-Prüfung ==="
Write-Host "AppRoot: $AppRoot"

if (-not (Test-Path $AppRoot)) {
    throw "AppRoot existiert nicht: $AppRoot"
}

$rootItem = Get-Item $AppRoot
$rootPath = $rootItem.FullName -replace '[\\/]+$', ''
$pattern = '^(<<<<<<<|=======|>>>>>>>)'
$matches = @()

Get-ChildItem -Path $rootPath -Recurse -File -Force | ForEach-Object {
    if (Test-IsExcludedPath $_.FullName $rootPath) {
        return
    }

    $fileMatches = Select-String -Path $_.FullName -Pattern $pattern -ErrorAction SilentlyContinue
    if ($fileMatches) {
        $matches += $fileMatches
    }
}

if ($matches.Count -gt 0) {
    Write-Host "FEHLER: Merge-Konfliktmarker gefunden:" -ForegroundColor Red
    $matches | ForEach-Object {
        Write-Host ("{0}:{1}: {2}" -f $_.Path, $_.LineNumber, $_.Line.Trim())
    }
    Write-Host ""
    Write-Host "Nicht manuell im GitHub-Webeditor 'halb' auflösen. Empfohlen: PR schließen, Branch frisch von aktuellem main erstellen, lokal prüfen, neuer PR."
    exit 1
}

Write-Host "OK: Keine Merge-Konfliktmarker gefunden."
