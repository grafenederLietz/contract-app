# GitHub-Konflikte lösen (einfach & sicher)

Wenn GitHub zeigt: **"This branch has conflicts that must be resolved"**, dann so vorgehen.

## 1) Konflikt-Editor öffnen
1. PR öffnen
2. Auf **Resolve conflicts** klicken

## 2) Was bei deinen Konflikten auswählen?
Bei den betroffenen PHP-Dateien (`contract_create.php`, `contracts.php`, ...):
- In der Konfliktzeile auf **Accept both changes** klicken
- Danach die Marker-Zeilen **manuell löschen**:
  - `<<<<<<< ...`
  - `=======`
  - `>>>>>>> ...`

## 3) Konflikt korrekt zusammenführen (wichtig)
Im `<head>`-Bereich muss am Ende so etwas stehen:
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/app.css">
<title>...</title>
```

Das heißt: 
- **Viewport + app.css behalten** (Styling)
- **Title behalten** (seitenspezifisch)
- **Keine Konfliktmarker** im File lassen

## 4) Speichern
1. Unten auf **Mark as resolved**
2. Für alle Konflikt-Dateien wiederholen
3. Danach **Commit merge**

## 5) PR mergen
- Zurück zur PR
- **Merge pull request**
- **Confirm merge**

## 6) Falls du unsicher bist (sicherste Variante)
Wenn sehr viele Konflikte sind:
1. PR schließen (nicht mergen)
2. Neuen Branch von `main` erstellen
3. Änderungen erneut sauber einspielen
4. Neue PR erstellen

So vermeidest du kaputte Marker in produktiven Dateien.
