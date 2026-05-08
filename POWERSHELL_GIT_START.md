# Git in PowerShell – genau für deinen Fehlerfall

## Warum der Fehler kam
- `fatal: not a git repository` kam, weil du **nicht im geklonten Ordner** warst.
- `cd /c/...` ist **Git Bash-Syntax**, nicht PowerShell.

## So geht es in PowerShell korrekt
1. Nach dem Clone in den Repo-Ordner wechseln:
```powershell
cd C:\Users\a.grafeneder\contract-app
```

2. Prüfen, ob `.git` vorhanden ist:
```powershell
dir -Force
```
Du musst einen Ordner `.git` sehen.

3. Dann erst pull ausführen:
```powershell
git pull origin main
```

## Wenn du den Code auf den Serverpfad legen willst
```powershell
cd C:\WebApps
# Nur wenn der Zielordner noch NICHT existiert:
git clone https://github.com/grafenederLietz/contract-app
cd C:\WebApps\contract-app
git pull origin main
```

## Wichtig
- In PowerShell immer `C:\...` Pfade verwenden.
- `/c/...` nur in Git Bash verwenden.

## Dein konkreter Fall ("already exists" + "not a git repository")
Wenn `contract-app` schon existiert, **nicht erneut clonen**, sondern hineingehen:

```powershell
cd C:\WebApps\contract-app
git status
git pull origin main
```

Wenn der Ordner alt/kaputt ist und du wirklich neu clonen willst:

```powershell
cd C:\WebApps
Rename-Item contract-app contract-app_backup_$(Get-Date -Format yyyyMMdd_HHmm)
git clone https://github.com/grafenederLietz/contract-app
cd C:\WebApps\contract-app
git pull origin main
```

Kurzregel:
- `git clone` nur **einmal** pro Zielordner
- Danach immer `cd ...\contract-app` und dann `git pull`

## Wenn `main` schon aktuell ist und Branch-Merge nur Konflikte macht
Du brauchst in diesem Fall **keinen** Merge von `main` in den `codex/...` Branch.

So kommst du sauber zurück auf einen einzigen aktuellen Stand:

```powershell
# Falls gerade ein konfliktbehafteter Merge offen ist:
git merge --abort

# Auf main zurück:
git checkout main
git pull origin main

# Optional: problematischen lokalen Branch löschen:
git branch -D codex/review-contract-app-repository-s0oxp5

# Prüfen:
git status
```

Erwartung: `On branch main` + `working tree clean`.

Dann arbeitest du nur noch auf `main` oder auf einem **neuen** Branch von `main`.
