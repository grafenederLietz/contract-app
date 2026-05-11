# Sauberer Neustart: `codex/...` als neuer `main`

> Achtung: Das überschreibt den bisherigen `main`-Verlauf auf GitHub.

## 1) Sicherheits-Backup erstellen (Pflicht)
```powershell
cd C:\WebApps\contract-app
git checkout main
git pull origin main
git tag backup-main-$(Get-Date -Format yyyyMMdd-HHmm)
git push origin --tags
```

## 2) Auf den gewünschten Branch wechseln
```powershell
git checkout codex/review-contract-app-repository-s0oxp5
git status
```

## 3) Diesen Branch als neuen `main` nach GitHub pushen
```powershell
git push origin codex/review-contract-app-repository-s0oxp5:main --force-with-lease
```

## 4) Lokal `main` neu aufsetzen
```powershell
git checkout main
git fetch origin
git reset --hard origin/main
git status
```

## 5) Alte Branches aufräumen (optional)
### Lokale Branches löschen
```powershell
git branch -D codex/review-contract-app-repository-s0oxp5
```

### Remote-Branches löschen
```powershell
git push origin --delete <branch-name>
```

## 6) Team-Hinweis
Alle Teammitglieder müssen danach einmalig synchronisieren:
```powershell
git fetch origin
git checkout main
git reset --hard origin/main
```
