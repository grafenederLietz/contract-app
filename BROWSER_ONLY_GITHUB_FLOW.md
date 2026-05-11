# GitHub nur im Browser: Schritt-für-Schritt

## A) Mit CLI (empfohlen)
1. `cd /c/WebApps/contract-app`
2. `git checkout main`
3. `git pull`
4. `git checkout -b feature/fix-stand`
5. Dateien ändern
6. `git add .`
7. `git commit -m "Änderungen"`
8. `git push -u origin feature/fix-stand`
9. In GitHub PR öffnen und mergen

## B) 100% Browser (ohne CLI)
Ja, geht – aber nur wenn Konflikte klein sind.

1. GitHub Repo öffnen
2. Auf Branch-Dropdown -> neuen Branch erstellen
3. Datei öffnen -> Stift-Symbol -> ändern -> "Commit changes"
4. Für alle Dateien wiederholen
5. "Compare & pull request" klicken
6. Falls Konflikte:
   - "Resolve conflicts"
   - "Accept both changes"
   - Konfliktmarker löschen (`<<<<<<<`, `=======`, `>>>>>>>`)
   - "Mark as resolved" -> "Commit merge"
7. "Merge pull request" -> "Confirm merge"

## C) Wichtig
- 100% Browser ist möglich, aber bei vielen Dateien fehleranfälliger.
- Für große Umstrukturierungen (wie bei dir) ist CLI sicherer.

## Warnung: GitHub-Web-Konfliktlösung

Wenn GitHub beim Merge Konflikte in PHP-, CSS-, PowerShell- oder Config-Dateien anzeigt, nicht blind im Webeditor akzeptieren. In diesem Projekt sind dadurch mehrfach Konfliktmarker wie `<<<<<<<`, `=======` und `>>>>>>>` in `main` gelandet.

Sicherer Ablauf:

1. PR nicht mergen, solange Konflikte angezeigt werden.
2. Neuen Branch vom aktuellen `main` erstellen.
3. Änderungen sauber neu anwenden.
4. Vor dem PR ausführen:
   - lokal: `./scripts/sanity_check.sh`
   - auf Windows/Server: `powershell -ExecutionPolicy Bypass -File scripts\check_conflict_markers.ps1 -AppRoot C:\WebApps\contract-app`
5. Erst mergen, wenn keine Konfliktmarker und keine PHP-Syntaxfehler gefunden werden.
