# Security Review – Phase 1 (Ist-Stand)

## Scope
- `public/`
- `src/`
- `config/`

## Ergebnis (Kurz)
- **Positiv:** CSRF-Mechanik vorhanden, Prepared-Statements vielfach genutzt, zentrale Config vorhanden.
- **Status 2026-05-11:** Direkte `die(...)`-Abbrüche in PHP-Endpunkten wurden entfernt; Upload-Prüfungen wurden in einen gemeinsamen Helper ausgelagert. Verbleibend sind weitere Härtungen wie Config-/Storage-Entkopplung und Autorisierungsreview.

## Funde (priorisiert)

### Kritisch
1. **Direkte Fehlerausgaben in produktiven Endpunkten**
   - Status: behoben; Endpunkte nutzen `app_log(...)` plus `app_abort(...)` statt direkter SQL-/Systemausgaben.

2. **Inkonsequente Access-Error-Behandlung**
   - Status: behoben; Access-Fehler laufen über `app_abort('Zugriff verweigert.', 403)`.

### Hoch
3. **Upload-Validierung zentralisieren**
   - Status: behoben; gemeinsame Upload-Prüfungen liegen in `src/upload.php` und werden von `public/contract_create.php` sowie `public/contract_edit.php` genutzt.

4. **Datei-Download Fehlerpfade**
   - Status: behoben; `public/file_download.php` nutzt einheitlich `app_abort(...)`, prüft Lesbarkeit und sendet konsistente Download-Header.

### Mittel
5. **Gemischte DB-Zugriffsstile (`query` vs `db_prepare`)**
   - z. B. `public/users.php`, `public/departments.php`, `public/locations.php`.
   - Risiko: erhöht Wartungs- und Sicherheitsrisiko bei Erweiterungen.

6. **Hardcoded Pfad-Defaults für Upload-Basis**
   - Grundsätzlich über Konstante geregelt, aber Migration auf ENV/Secret-Config empfohlen.

## Sofortmaßnahmen (nächster Schritt)
1. [x] Alle `die('SQL Fehler ...')` ersetzen durch `app_log(...)` + `app_abort('Datenbank-Fehler.', 500)`.
2. [x] Alle `die('Zugriff verweigert.')` ersetzen durch `app_abort('Zugriff verweigert.', 403)`.
3. [x] Download-Fehlerpfade auf `app_abort(...)` vereinheitlichen.
4. [x] Upload-Prüfungen in gemeinsame Helper-Funktion auslagern.

## Ergebnisziel Phase 1
- Kein direkter SQL-/Systemfehler für Endnutzer sichtbar.
- Einheitliche HTTP-Statuscodes und Logging für Security-relevante Fehlerfälle.
