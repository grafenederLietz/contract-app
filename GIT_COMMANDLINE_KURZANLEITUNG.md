# Git Commandline – einfache Kurzanleitung (Windows)

## 0) Einmalig installieren
- **Git for Windows** installieren
- Danach "Git Bash" öffnen

## 1) In den Projektordner wechseln
```bash
cd /c/WebApps/contract-app
```

## 2) Status prüfen
```bash
git status
```
- Zeigt dir, welche Dateien geändert sind.

## 3) Aktuellen Stand von GitHub holen
```bash
git pull
```

## 4) Änderungen speichern (Commit)
```bash
git add .
git commit -m "Beschreibung der Änderung"
```

## 5) Änderungen zu GitHub hochladen
```bash
git push
```

## 6) Neuer Branch für Änderungen (empfohlen)
```bash
git checkout -b feature/mein-aenderungspunkt
```
Nach dem Arbeiten:
```bash
git add .
git commit -m "Feature fertig"
git push -u origin feature/mein-aenderungspunkt
```
Dann in GitHub einen Pull Request öffnen.

## 7) Typischer Tagesablauf (sicher)
```bash
git checkout main
git pull
git checkout -b feature/xyz
# Dateien ändern
git add .
git commit -m "xyz"
git fetch origin
git rebase origin/main
git push -u origin feature/xyz
```

## 8) Wenn ein Fehler passiert
- Letzten Commit rückgängig (nur lokal):
```bash
git reset --soft HEAD~1
```
- Lokale Änderungen verwerfen (Achtung!):
```bash
git restore .
```

## 9) Nützliche Befehle
```bash
git log --oneline -n 10
git branch
git checkout main
```
