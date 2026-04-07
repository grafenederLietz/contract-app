# Projekt-Info-Checkliste (Vertragsmanagement PHP + MariaDB)

Diese Checkliste hilft uns, alle offenen Punkte strukturiert zu sammeln, damit wir die Anwendung stabil fertigstellen können.

## 1) Zugriff & Umgebung
- Repository-Zugriff (GitHub): Schreibrechte / Branch-Strategie
- Zielumgebung: Windows Server 2022 + IIS (Version), PHP-Version, MariaDB-Version
- Welche Umgebungen gibt es? (DEV / TEST / PROD)
- Backup-Konzept vor Änderungen (Code + Datenbank + Uploads)

## 2) Aktueller Stand (Ist-Analyse)
- Welche Seiten/Funktionen funktionieren bereits sicher?
- Welche konkreten Fehler treten aktuell auf? (inkl. URL, Fehlermeldung, Schritte)
- Priorisierung der Fehler (kritisch / hoch / mittel / niedrig)
- Welche Dateien sind unvollständig oder „Work in Progress“?

## 3) Fachliche Anforderungen
- Rollenmodell (z. B. admin, editor, viewer) inkl. Rechte pro Rolle
- Sichtbarkeit von Verträgen nach Abteilung/Standort/Benutzer
- Exakte Ampellogik:
  - Grün: ab wann?
  - Gelb: wie viele Tage/Monate vor Ablauf?
  - Rot: ab wann?
  - Sonderfälle (automatische Verlängerung, Kündigungsfrist)
- Pflichtfelder je Vertrag (Lieferant, Start/Ende, Verantwortliche, etc.)

## 4) Uploads & Dokumente
- Erlaubte Dateitypen und maximale Dateigröße
- Soll ein Vertrag mehrere Dateien haben? (Versionierung?)
- Zielpfade für Uploads (lokal/Netzwerk) und Rechte des IIS-App-Pools
- Download-Schutz: Wer darf welche Datei öffnen?

## 5) Sicherheit (pragmatisch, intern, aber sauber)
- Authentifizierung (Passwortregeln, Login-Fehlerlimits, Session-Timeout)
- Autorisierung auf Seiten- und Datenebene
- CSRF-Schutz auf allen schreibenden Formularen
- SQL-Injection-Schutz (Prepared Statements überall)
- Ausgabe-Escaping gegen XSS
- Sichere Datei-Uploads (MIME/Extension-Checks, zufällige Dateinamen)
- Logging/Audit (wer hat was wann geändert)

## 6) Datenbank & Datenqualität
- Vollständiges Schema (DDL/Export)
- Beispiel-/Testdaten je Tabelle
- Fremdschlüssel und gewünschtes Löschverhalten (RESTRICT/CASCADE)
- Indizes für häufige Filter (Status, Ablaufdatum, Benutzer, Abteilung, Standort)

## 7) Zielarchitektur für wartbaren Code
- Gewünschte Struktur (z. B. klar getrennt in:
  - `public/` für Einstiegspunkte
  - `src/` für Logik/Services
  - `config/` für Konfiguration
  - `templates/` für Views)
- Konventionen (Namensschema, Coding-Style, Fehlermanagement)
- Welche Teile sollen zuerst refaktoriert werden?

## 8) Test & Abnahme
- Kritische User-Flows (Login, Vertrag anlegen, Upload, Suche, Fristen)
- Erwartete Ergebnisse je Flow
- Browser/Clients im Unternehmen
- Abnahmekriterien für „stabil genug für Testphase"

## 9) Bitte als Nächstes senden
1. **Code-Stand**: aktueller vollständiger Stand (alle PHP-Dateien + ggf. SQL/Config ohne Secrets)
2. **Fehlerliste**: Top 10 Probleme mit Reproduktionsschritten
3. **DB-Export**: Struktur + anonymisierte Beispieldaten
4. **Rollen/Rechte-Matrix**: Wer darf was sehen/bearbeiten?
5. **Ampellogik exakt**: Schwellwerte und Regeln schriftlich
6. **Upload-Regeln**: Dateitypen, Größen, Ablage, Berechtigungen
7. **Priorität**: Was muss zwingend in Phase 1 fertig sein?

## Arbeitsvorschlag (phasenweise)
1. **Stabilisieren**: kritische Fehler + Sicherheits-Basics schließen
2. **Vereinheitlichen**: zentrale Helper/Services statt Logik in vielen Einzeldateien
3. **Abnahmereife**: Smoke-Tests + Benutzer-Testphase
4. **UI/CSS-Polish**: erst nach stabiler Funktionalität

