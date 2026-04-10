# Security Review – Phase 1 (Ist-Stand)

## Scope
- `public/`
- `src/`
- `config/`

## Ergebnis (Kurz)
- **Positiv:** CSRF-Mechanik vorhanden, Prepared-Statements vielfach genutzt, zentrale Config vorhanden.
- **Kritisch/Mittel:** Mehrere direkte `die(...)`-Abbrüche inkl. potenzieller Informationslecks, inkonsistente Fehlerbehandlung, Upload/Download-Checks teilweise uneinheitlich.

## Funde (priorisiert)

### Kritisch
1. **Direkte Fehlerausgaben in produktiven Endpunkten**
   - Beispiele:
     - `public/users.php` -> `die('SQL Fehler: ...')`
     - `public/departments.php` -> `die('SQL Fehler: ...')`
     - `public/locations.php` -> `die('SQL Fehler: ...')`
   - Risiko: Information Disclosure (DB-Interna, Query-Kontext).

2. **Inkonsequente Access-Error-Behandlung**
   - Viele Stellen nutzen `die('Zugriff verweigert.')` statt `app_abort(403)`.
   - Risiko: uneinheitliches Verhalten, schlechter auditierbar.

### Hoch
3. **Upload-Validierung nicht zentralisiert**
   - Upload-Regeln/Fehlerpfade verstreut in `public/contract_create.php` und `public/contract_edit.php`.
   - Risiko: Regelabweichungen bei zukünftigen Änderungen.

4. **Datei-Download Fehlerpfade mit `die(...)`**
   - `public/file_download.php` nutzt teilweise direkte `die(...)` bei Fehlern.
   - Risiko: inkonsistente Response-Semantik.

### Mittel
5. **Gemischte DB-Zugriffsstile (`query` vs `db_prepare`)**
   - z. B. `public/users.php`, `public/departments.php`, `public/locations.php`.
   - Risiko: erhöht Wartungs- und Sicherheitsrisiko bei Erweiterungen.

6. **Hardcoded Pfad-Defaults für Upload-Basis**
   - Grundsätzlich über Konstante geregelt, aber Migration auf ENV/Secret-Config empfohlen.

## Sofortmaßnahmen (nächster Schritt)
1. Alle `die('SQL Fehler ...')` ersetzen durch `app_log(...)` + `app_abort('Datenbank-Fehler.', 500)`.
2. Alle `die('Zugriff verweigert.')` ersetzen durch `app_abort('Zugriff verweigert.', 403)`.
3. Download-Fehlerpfade auf `app_abort(...)` vereinheitlichen.
4. Upload-Prüfungen in gemeinsame Helper-Funktion auslagern.

## Ergebnisziel Phase 1
- Kein direkter SQL-/Systemfehler für Endnutzer sichtbar.
- Einheitliche HTTP-Statuscodes und Logging für Security-relevante Fehlerfälle.
