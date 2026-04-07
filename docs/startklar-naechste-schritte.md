# Startklar – was ich von dir **noch** brauche (nach deinen neuen Infos)

Danke, deine Screenshots haben schon viel geklärt. Damit ich **direkt mit dem Fixen** anfangen kann, fehlen nur noch diese Punkte:

## Bereits bestätigt (aus deinen Screenshots)
- IIS Site: `contract-app` mit Pfad `C:\WebApps\contract-app\public` und Binding `:8080`.
- App Pool: `contract-app`, **No Managed Code**, Integrated.
- Projektstruktur vorhanden: `config/`, `public/`, `src/`, `scripts/`, `Uploads/`, `vendor/`.
- PHP/MariaDB Installationspakete vorhanden (PHP 8.3.x, MariaDB 12.2.x).

## Was jetzt noch fehlt (Blocker für den Start)
1. **Aktueller Code als ZIP oder Git-Push**
   - Ich brauche den **genauen Stand**, der auf dem Server läuft (alle PHP-Dateien).

2. **config/config.php (anonymisiert)**
   - DB-Host, DB-Name, User-Name (Passwort bitte schwärzen oder Dummy).
   - Upload-Basispfad-Konfiguration.

3. **Top-5 Fehler mit Reproduktionsschritten**
   - Format je Fehler:
     - Seite/URL
     - Aktion
     - Ist-Ergebnis (Fehlermeldung/Symptom)
     - Soll-Ergebnis

4. **Rollen-/Rechte-Regeln in 1 Tabelle**
   - Wer darf: Vertrag sehen, erstellen, bearbeiten, löschen, Datei herunterladen.
   - Falls Abteilungs-/Standortfilter gelten: kurz dazuschreiben.

5. **Ampellogik final (1–2 Sätze je Farbe)**
   - Grün / Gelb / Rot in Tagen vor Vertragsende.
   - Regel für Auto-Renew + Kündigungsfrist.

6. **Datei-Upload-Regeln**
   - Erlaubte Dateitypen (z. B. pdf/docx).
   - Max. Dateigröße.
   - Mehrere Dateien pro Vertrag erlaubt? (Ja/Nein)

## Optional, aber hilfreich
- Ein SQL-Dump (Schema + 5–10 anonymisierte Testdatensätze je Haupttabelle).
- 1 Testnutzer pro Rolle (oder ich lege sie in DEV an).

## Sobald du das sendest, starte ich so
1. Fehler reproduzieren und priorisieren.
2. Kritische Sicherheits-/Stabilitätsfixes.
3. Refactoring auf zentrale Funktionen (damit nicht 100 Dateien angefasst werden müssen).
4. Danach UI/CSS-Phase für User-Test.
