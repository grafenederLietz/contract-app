# V1-Finalisierung & Styling-Plan (interne Präsentation)

Ziel: Eine stabile, präsentierbare Version ohne neue Features.

## Phase 0 – Backup (vor jeder Änderung)
- Code-Backup (ZIP/Git-Tag)
- Datenbank-Backup (`contractdb` Dump)
- Upload-Ordner-Backup (`C:\Vertragsdaten\Uploads`)

## Phase 1 – Freeze vom Grundgerüst
- Keine neuen Features, nur Bugfixes/Qualität
- Seiten- und Navigationsstruktur final festhalten
- Rollen/Rechte kurz gegenprüfen (admin/editor)

## Phase 2 – Minimales UI-Styling (ohne Framework-Zwang)
- Einheitliche Layout-Basis (Header, Navigation, Content-Bereich)
- Einheitliche Form-Styles (Inputs, Selects, Buttons, Fehlermeldungen)
- Tabellen-Styles (Verträge, Benutzer, Standorte, Abteilungen)
- Statusfarben für Ampellogik konsistent darstellen

## Phase 3 – Präsentations-Readiness
- Smoke-Test aller Kernflows:
  - Login / Logout
  - Dashboard
  - Verträge anzeigen / filtern / öffnen
  - Vertrag anlegen / bearbeiten
  - Datei-Upload / Download
- Daten für Demo vorbereiten (2–3 Beispielverträge pro Status)
- Kurze Präsentationsagenda (5–10 min)

## Phase 4 – Feedbackrunde
- Interne Präsentation
- Feedbackliste strukturieren:
  - Muss (vor nächstem Rollout)
  - Soll (nächster Sprint)
  - Kann (später)

## Definition of Done (V1)
- Keine Laufzeitfehler in Kernflows
- Einheitliches Styling auf allen Hauptseiten
- Interne Demo erfolgreich durchgeführt
- Änderungswünsche dokumentiert und priorisiert
