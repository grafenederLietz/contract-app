param(
    [string]$UploadRoot = "C:\Vertragsdaten\Uploads",
    [string[]]$GrantReadUsers = @("BUILTIN\Administrators"),
    [string[]]$GrantModifyUsers = @("IIS_IUSRS"),
    [switch]$Apply
)

$ErrorActionPreference = 'Stop'

function Write-Section($Title) {
    Write-Host ""
    Write-Host "=== $Title ==="
}

Write-Section "Upload-ACL Diagnose"
Write-Host "UploadRoot: $UploadRoot"
Write-Host "Modus:      $(if ($Apply) { 'ÄNDERN' } else { 'NUR ANZEIGEN (-Apply zum Ändern)' })"

if (-not (Test-Path $UploadRoot)) {
    if ($Apply) {
        New-Item -ItemType Directory -Path $UploadRoot -Force | Out-Null
        Write-Host "UploadRoot wurde erstellt."
    } else {
        Write-Host "FEHLER: UploadRoot existiert nicht. Mit -Apply kann der Ordner erstellt werden."
        exit 2
    }
}

Write-Section "Aktuelle ACL"
$acl = Get-Acl $UploadRoot
$acl.Access | Select-Object IdentityReference, FileSystemRights, AccessControlType, IsInherited | Format-Table -AutoSize

if (-not $Apply) {
    Write-Host ""
    Write-Host "Hinweis: Direkter Explorer-Zugriff ist nicht der normale Benutzerweg; Downloads sollen über die App erfolgen."
    Write-Host "Wenn Administratoren im Explorer 'Access denied' erhalten, dieses Skript mit -Apply als Administrator ausführen."
    exit 0
}

Write-Section "ACL ergänzen"
$inheritanceFlags = [System.Security.AccessControl.InheritanceFlags]::ContainerInherit -bor [System.Security.AccessControl.InheritanceFlags]::ObjectInherit
$propagationFlags = [System.Security.AccessControl.PropagationFlags]::None
$accessType = [System.Security.AccessControl.AccessControlType]::Allow

foreach ($identity in $GrantReadUsers) {
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($identity, "ReadAndExecute", $inheritanceFlags, $propagationFlags, $accessType)
    $acl.SetAccessRule($rule)
    Write-Host "ReadAndExecute gesetzt für: $identity"
}

foreach ($identity in $GrantModifyUsers) {
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($identity, "Modify", $inheritanceFlags, $propagationFlags, $accessType)
    $acl.SetAccessRule($rule)
    Write-Host "Modify gesetzt für: $identity"
}

Set-Acl -Path $UploadRoot -AclObject $acl

Write-Section "Neue ACL"
(Get-Acl $UploadRoot).Access | Select-Object IdentityReference, FileSystemRights, AccessControlType, IsInherited | Format-Table -AutoSize

Write-Host ""
Write-Host "Fertig. Bitte danach Upload/Download erneut über die App testen."
