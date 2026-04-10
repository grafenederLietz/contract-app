# GitHub PR: Gibt es "Auto Accept All Changes"?

Kurz: **Nein**, nicht für Konfliktdateien im Web-Editor.

## Was es gibt
1. **Auto-merge** in GitHub
   - Funktioniert nur, wenn:
     - keine Merge-Konflikte vorhanden sind
     - alle Regeln (Reviews/Checks) erfüllt sind

2. **Merge Queue** (optional, Team/Org Feature)
   - Merged PRs in geordneter Reihenfolge
   - Verhindert viele race-condition Konflikte

3. **CLI-Strategie für Konflikte**
   - Bei vielen Konflikten ist CLI sicherer als Web-Editor.

## Warum kein "Accept all"
- GitHub verhindert pauschales Blind-Mergen von Konfliktmarkern,
  damit keine kaputten Dateien in `main` landen.

## Empfehlung für dich
- Kleine PRs (5-10 Dateien)
- Strukturänderungen (Move/Rename) separat mergen
- Danach Funktionsänderungen in eigener PR
- Branch vor PR immer aktualisieren (`git fetch` + `git rebase origin/main`)
