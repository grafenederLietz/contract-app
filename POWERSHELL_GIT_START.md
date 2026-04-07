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
