# Aktueller Projektstatus

## Stand heute
- Repository-Struktur entspricht dem Deploy-Ziel:
  - `config/`
  - `public/`
  - `src/`
  - `scripts/`
  - `Uploads/`
  - `vendor/`
- `main` ist als alleiniger aktiver Hauptstand vorgesehen.
- Kernfunktionen laufen laut Rückmeldung ohne Laufzeitfehler.

## Bereits umgesetzt
- CSRF-Schutz in Login/Create/Edit-Flows.
- Zentrale Konfiguration in `config/config.php`.
- Einheitliches Styling über `public/app.css`.
- Upload-/Download-Handling gehärtet.

## Nächste sinnvolle Schritte (ohne neue Features)
1. **Release-Baseline markieren**
   - Git Tag setzen (z. B. `v1-internal-demo`).
2. **Smoke-Test-Protokoll erstellen**
   - Login, Dashboard, Verträge, Upload/Download, Benutzer/Standorte/Abteilungen.
3. **Staging-Backup automatisieren**
   - DB-Dump + Upload-Ordner + Code-Export.
4. **Interne Demo vorbereiten**
   - Demo-Datensätze + kurzer Ablauf für Feedbackrunde.

## Go/No-Go Kriterium für interne Präsentation
- Keine PHP Errors in Kernseiten
- Upload/Download funktioniert
- Rollenrechte funktionieren
- Ampelübersicht und kritische Verträge korrekt sichtbar
