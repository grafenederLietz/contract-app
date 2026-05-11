# Master To-Do & Umsetzungsreihenfolge

## Phase 0 – Stabiler Ausgangspunkt (sofort)
- [ ] Secrets aus Code entfernen (DB-Passwort-Fallback, feste Pfade nur über ENV/Config)
- [ ] Security-Baseline prüfen (Input-Validation, Access-Control, Session/Cookie, Upload/Download)
- [ ] Einheitliche Projektstruktur final festziehen (`config/`, `public/`, `src/`, `scripts/`, `Uploads/`, `vendor/`)

## Phase 1 – Sicherheits-Härtung Anwendung
- [ ] Vollständiger Security-Review (OWASP Top 10 Fokus)
- [ ] Fehlermeldungen vereinheitlichen + Logging ohne Datenleck
- [ ] Upload-Policy schärfen (MIME + Extension + Size + Storage-Isolation)
- [ ] Autorisierungs-Review: Zugriff nur nach Standort/Abteilung/Rolle
- [ ] Vertretungs-Logik (zusätzliche Verantwortliche) mit Rechteprüfung vorbereiten

## Phase 2 – Datenbank- und Config-Härtung (MariaDB)
- [ ] Schema-Review + FK/Index-Review + Constraints nachziehen
- [ ] Standort-Tabelle um Kürzel für CSV-Import erweitern
- [ ] DB-User-Rechte minimieren (least privilege)
- [ ] MariaDB-Konfig prüfen (TLS, bind-address, sql_mode, slow log, backup policy)

## Phase 3 – IIS + HTTPS Härtung
- [ ] HTTPS-only erzwingen (Redirect HTTP->HTTPS)
- [ ] HSTS aktivieren
- [ ] Security Header ergänzen (CSP, X-Frame-Options, etc.)
- [ ] Directory Browsing/Traversal-Schutz prüfen
- [ ] Upload- und Script-Ausführungspfade isolieren

## Phase 4 – Dateiablage auf Fileserver
- [ ] Zielmodell festlegen:
  - [ ] Option A: Ordner pro Vertrags-ID
  - [ ] Option B: Standort/Abteilung-Pfad
- [ ] Storage-Service kapseln (lokal/netzwerkshare austauschbar)
- [ ] Automatisches Verschieben bei Standort/Abteilungsänderung
- [ ] Berechtigungsmodell auf Dateiebene sicherstellen

## Phase 5 – Fachlogik erweitern
- [ ] Rollenbasiertes Menükonzept: schlankes User-Menü + separates Admin-Menü
- [ ] Styling flexibel halten (Theme-Variablen/Komponenten statt Seiten-spezifischem CSS)
- [ ] Ampel-/Statuslogik neu designen
- [ ] Admin-GUI für Ampel-Regeln
- [ ] Verantwortliche nach Standort filtern
- [ ] Vertretungen je Vertrag hinzufügen

## Phase 6 – Integration & Automatisierung
- [ ] E-Mail Versand via Microsoft 365 Exchange Online
- [ ] CSV-Import bestehender Verträge
- [ ] Import-Validierung/Mapping inkl. Standort-Kürzel

## Phase 7 – Qualität & Rollout
- [ ] End-to-end Smoke-Test-Checkliste
- [ ] Audit-Logs und Monitoring
- [ ] Rollout-Plan + Fallback

---

## Aktueller Ist-Stand Ampellogik (Code)
- Rot/Überfällig/Gelb/Grün wird aktuell über Tage bis Kündigungsstichtag berechnet.
- Schwellen aktuell im Code:
  - Überfällig: < 0 Tage
  - Rot: <= 90 Tage
  - Gelb: <= 180 Tage
  - Grün: > 180 Tage
- Grau bei Status: `terminated`, `ended`, `archived`.
