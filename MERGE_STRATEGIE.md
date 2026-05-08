# Auto-Merge ohne manuelle Konflikte (für dieses Projekt)

## Warum die Konflikte bei dir gerade passieren
Du hast in `main` noch Dateien wie `login.php`, `dashboard.php` im Root,
aber der neue Stand verschiebt diese nach `public/`.
Wenn beide Branches dieselben Dateien parallel ändern, entstehen Konflikte.

## Sichere Vorgehensweise (empfohlen)
1. **Immer zuerst main aktualisieren**
   - `git checkout main`
   - `git pull`
2. **Feature-Branch frisch von main erstellen**
   - `git checkout -b feature/<name>`
3. **Nur in diesem Branch ändern**
4. **Vor PR erneut main in Branch holen**
   - `git checkout feature/<name>`
   - `git fetch origin`
   - `git rebase origin/main`  (oder `git merge origin/main`)
5. **Erst wenn lokal konfliktfrei, PR öffnen**

## Für deine aktuelle Situation (schnellster Weg)
Wenn du schon große Konflikte hast:
1. PR schließen
2. Neuen Branch **direkt von aktuellem main** erstellen
3. Den funktionierenden Stand erneut committen (mit neuer Struktur)
4. Neue PR öffnen

## Regeln, damit Auto-Merge klappt
- Nur **eine** aktive PR gleichzeitig für dieselben PHP-Dateien
- Keine Änderungen direkt in `main`
- Bei Strukturänderungen (Root -> `public/`) zuerst eine eigene PR nur für Move/Struktur
- Danach erst Funktionsänderungen in separaten PRs

## Optional (GitHub UI)
- In PRs "Require branches to be up to date before merging" aktivieren
- Kleine PRs (max. 5-10 Dateien) bevorzugen
