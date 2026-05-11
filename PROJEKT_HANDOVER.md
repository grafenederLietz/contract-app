# Projekt-Handover: Contract App

Stand: 2026-05-11

Diese Datei ist die zentrale Übergabe für einen neuen Codex-Arbeitsbereich. Sie fasst Projektziel, aktuellen Stand, Server-/DB-Infos, bekannte Probleme, Arbeitsregeln und nächste To-dos zusammen.

## 1. Projektziel

Die Anwendung ist ein internes Vertragsmanagement in PHP mit MariaDB-Datenbank auf einem Windows Server 2022 mit IIS.

Kernziel:

- Verträge zentral verwalten.
- Verträge nach Standort und Abteilung berechtigen.
- Kritische Vertragsfristen über Dashboard und Ampellogik sichtbar machen.
- Dokumente zu Verträgen hochladen und sicher herunterladen.
- Interne Nutzer sollen ohne komplexe Enterprise-Sicherheitsarchitektur, aber mit solider Basis-Sicherheit arbeiten können.

## 2. Zielstruktur im Repository und auf dem Server

Das GitHub-Repository soll 1:1 nach `C:\WebApps\contract-app` auf den Server übernommen werden können.

Wichtige Ordner:

```text
config/              Zentrale Konfiguration, lokale Secrets-Vorlage
public/              IIS-Webroot / öffentliche PHP-Einstiegspunkte
public/assets/       Legacy-/Kompatibilitäts-Assetpfad
src/                 Wiederverwendbare PHP-Logik
scripts/             Diagnose-, Prüf- und Wartungsskripte
logs/                Lokales Logging, nicht für GitHub-Secrets verwenden
Uploads/             Platzhalter/Altstruktur, später durch Fileserver-Konzept ersetzen
vendor/              Platzhalter für spätere Dependencies
```

Wichtig:

- IIS soll auf `C:\WebApps\contract-app\public` zeigen.
- Die App-Dateien sollen per GitHub `main` und `git pull origin main` auf den Server kommen.
- `config/local.php` ist lokal auf dem Server und darf nicht in GitHub committed werden.

## 3. Aktueller Serverstand / Infrastruktur

Bekannte Informationen aus bisherigen Tests:

- Server: Windows Server 2022
- Hostname aus Diagnose: `SVWYADMIN01`
- App-Pfad: `C:\WebApps\contract-app`
- IIS-Webroot soll sein: `C:\WebApps\contract-app\public`
- PHP-Pfad auf dem Server wurde verwendet als: `C:\PHP\php.exe`
- MariaDB läuft lokal oder erreichbar über DB-Host aus `config/local.php`.
- Browser-Test erfolgte u. a. über IIS/Edge.
- Aktueller Login funktioniert wieder, nachdem `config/config.php` manuell sauber ersetzt wurde.

Bekannter PHP-Hinweis:

- Bei `C:\PHP\php.exe -l ...` erschien eine Warnung zu `pdo_firebird`.
- Diese Warnung war nicht der Login-Blocker.
- Der damalige Blocker war ein Parse Error durch Merge-Konfliktmarker in `config/config.php`.

## 4. Datenbank-Konfiguration

DB-Zugangsdaten dürfen nicht im Repository stehen.

Die Datei `config/config.php` lädt DB-Zugangsdaten ausschließlich aus:

```text
config/local.php
```

Diese Datei muss lokal am Server existieren und z. B. so aussehen:

```php
<?php

return [
    'db_host' => '127.0.0.1',
    'db_name' => 'contractdb',
    'db_user' => 'contractapp_user',
    'db_pass' => 'ECHTES_DB_PASSWORT_NUR_LOKAL',
];
```

Wichtig:

- `config/local.php` darf niemals nach GitHub committed werden.
- `config/local.php.example` ist nur eine Vorlage ohne echtes Passwort.
- DB-Passwörter können für die Datenbankverbindung nicht gehasht gespeichert werden, weil PHP das echte DB-Passwort zur Verbindung mit MariaDB benötigt.
- User-Passwörter in der Anwendung sollen dagegen per `password_hash()` / `password_verify()` behandelt werden.

## 5. Aktueller Code-Status

Aktueller funktionaler Stand laut Benutzer:

- Login funktioniert wieder.
- Dashboard war zuvor erreichbar.
- Vertragsübersicht funktionierte nach Korrektur von `contracts.php`.
- Grundfunktionen liefen vor dem Config-Problem ohne Fehler.
- Styling war grundsätzlich sichtbar, nachdem CSS unter `/app.css` erreichbar war.

Wichtige technische Punkte:

- Öffentliche Einstiegspunkte liegen unter `public/`.
- Gemeinsame Auth-/Zugriffslogik liegt unter `src/`.
- Zentrale DB-/Logging-/Abort-Helfer liegen in `config/config.php`.
- `config/config.php` muss sauber bleiben und darf keine Merge-Konfliktmarker enthalten.
- `scripts/verify_config.php` und `scripts/repair_server_checkout.ps1` dienen zur Prüfung einer sauberen Config.

## 6. Wichtiger Vorfall: PR-/Merge-/Config-Problem

Es gab wiederholt Parse Errors in `config/config.php`, z. B.:

```text
PHP Parse error: syntax error, unexpected token "<<", expecting end of file
```

Ursache:

- In GitHub/Serverstand waren Merge-Konfliktmarker oder zusammenkopierte alte Config-Fragmente enthalten.
- Beispiele waren doppelte Funktionsdeklarationen, alte typisierte Funktionsköpfe und ein altes Klartextpasswort.

Wichtigste Lektion:

- Ein Codex-Arbeitsstand gilt erst als übernommen, wenn ein echter GitHub Pull Request sichtbar, gemerged und danach per `git pull origin main` auf dem Server angekommen ist.
- Der Button `Weitergeben` in Codex teilt nur die Unterhaltung und erstellt keinen GitHub-PR.
- Der Button `PR anzeigen` kann auf einen bereits geschlossenen PR zeigen.
- Wenn im Dropdown nur `git apply kopieren` und `Patch kopieren` sichtbar ist, kann diese alte Codex-Aufgabe sehr wahrscheinlich keinen neuen PR mehr erzeugen.

## 7. Verbindlicher Workflow für neue Änderungen

Für den neuen Arbeitsbereich gilt:

1. Immer vom aktuellen GitHub `main` starten.
2. Änderungen in einem neuen Codex-Arbeitsbereich / neuen Branch durchführen.
3. Nach Änderungen muss ein echter PR in GitHub sichtbar sein.
4. Nur wenn der PR in GitHub unter `Pull requests` sichtbar ist, gilt er als erstellt.
5. PR mergen.
6. Server aktualisieren:

```powershell
cd C:\WebApps\contract-app
git checkout main
git pull origin main
git log -1 --oneline
git status
```

7. Config prüfen:

```powershell
C:\PHP\php.exe -l C:\WebApps\contract-app\config\config.php
C:\PHP\php.exe C:\WebApps\contract-app\scripts\verify_config.php
```

Optionaler gebündelter Nach-Pull-Check inkl. Konfliktmarker-Suche, `contract_edit.php`, Upload-Helper und Upload-Pfad-ACL:
Optionaler gebündelter Nach-Pull-Check inkl. `contract_edit.php`, Upload-Helper und Upload-Pfad-ACL:

```powershell
powershell -ExecutionPolicy Bypass -File C:\WebApps\contract-app\scripts\server_post_pull_check.ps1 -AppRoot C:\WebApps\contract-app -PhpExe C:\PHP\php.exe
```

8. IIS neu starten:

```powershell
iisreset
```

9. Browser-Test durchführen.


Wichtig bei GitHub-Web-Konflikten:

- Wenn GitHub beim Online-Merge Konflikte zeigt, die PHP-/PS-/CSS-Dateien betreffen, diese nicht im Browser halbautomatisch auflösen.
- Stattdessen PR schließen oder nicht mergen, neuen Branch vom aktuellen `main` erstellen, Änderungen neu anwenden, lokal bzw. per `scripts/sanity_check.sh` und serverseitig per `scripts/check_conflict_markers.ps1` prüfen und erst dann einen neuen PR erstellen.
- Konfliktmarker wie `<<<<<<<`, `=======`, `>>>>>>>` dürfen niemals nach `main` gelangen.

## 8. Lokale Serverregeln

Auf dem Server sollen keine getrackten Projektdateien manuell geändert werden, außer in einer Notfall-Reparatur.

Erlaubte lokale Datei:

```text
config/local.php
```

Nicht dauerhaft manuell ändern:

```text
config/config.php
public/*.php
src/*.php
scripts/*.ps1
scripts/*.php
public/app.css
```

Wenn der Serverstand kaputt ist:

```powershell
powershell -ExecutionPolicy Bypass -File C:\WebApps\contract-app\scripts\repair_server_checkout.ps1 -AppRoot C:\WebApps\contract-app -Branch main -Force -PhpExe C:\PHP\php.exe
```

Wenn der direkte Explorer-Zugriff auf `C:\Vertragsdaten\Uploads` für Administratoren mit `Access denied` fehlschlägt, zuerst die ACL nur anzeigen und danach bei Bedarf als Administrator reparieren:

```powershell
powershell -ExecutionPolicy Bypass -File C:\WebApps\contract-app\scripts\repair_upload_acl.ps1
powershell -ExecutionPolicy Bypass -File C:\WebApps\contract-app\scripts\repair_upload_acl.ps1 -Apply
```

Hinweis: Fachliche Downloads sollen weiterhin über die App und `file_download.php` erfolgen, nicht über direkte Dateisystempfade.

## 9. Aktuelle offene technische To-dos

### Phase 1: Codebasis stabilisieren

- Kompletten Code erneut prüfen, sobald ein neuer sauberer Arbeitsbereich vom aktuellen GitHub `main` gestartet wurde.
- Sicherstellen, dass `config/config.php` in GitHub exakt die funktionierende Server-Version enthält.
- Sicherstellen, dass keine Merge-Konfliktmarker im Repository sind.
- Sicherstellen, dass keine Klartext-Secrets im Repository sind.
- `scripts/sanity_check.sh` und `scripts/verify_config.php` vor PRs ausführen.
- Fehlerbehandlung weiter vereinheitlichen: keine direkten DB-Fehler an Benutzer ausgeben.
- Code vereinfachen, ohne spätere Module zu blockieren.

### Phase 2: Security Review / Hardening App

- Login-Rate-Limit prüfen/implementieren.
- Passwort-Policy prüfen/implementieren.
- CSRF-Abdeckung erneut vollständig prüfen.
- Upload-Validierung zentralisieren und weiter härten.
- Download nur nach Berechtigungsprüfung und bevorzugt als Attachment ausliefern.
- Content Security Policy evaluieren.
- Session-/Cookie-Einstellungen für HTTPS finalisieren.
- Directory Traversal verhindern und Webroot strikt auf `public/` begrenzen.
- Prüfen, ob Upload-/Dokumentenordner außerhalb des Webroots liegt.

### Phase 3: Datenbank prüfen

- Aktuelle MariaDB-Struktur erfassen.
- Tabellen, Spalten, Indizes, Foreign Keys prüfen.
- Benutzer-/Rollen-/Standort-/Abteilungsmodell dokumentieren.
- Standort-Tabelle um Standort-Kürzel für CSV-Import erweitern.
- DB-User-Rechte minimalisieren.
- MariaDB-Konfiguration hinsichtlich Sicherheit und Betrieb prüfen.
- Backup-/Restore-Prozess definieren.

### Phase 4: IIS / HTTPS / Webserver-Härtung

- IIS-Site auf HTTPS umstellen.
- Zertifikat einrichten.
- HTTP nach HTTPS umleiten.
- IIS Directory Browsing deaktivieren.
- Static File / MIME-Konfiguration prüfen.
- Zugriff auf `config/`, `src/`, `scripts/`, `logs/`, `Uploads/` per Web verhindern.
- PHP-FastCGI Handler Mapping dokumentieren.
- PHP-Extensions prüfen, z. B. fehlerhafte `pdo_firebird`-Warnung bereinigen.

### Phase 5: Dateiablage / Upload-Konzept

- Dokumente künftig auf Netzwerkshare / Fileserver speichern.
- Option A: Ordnerstruktur nach Standort und Abteilung.
- Option B: Ein Ordner pro Vertrags-ID.
- Empfehlung zur Vermeidung von Verschiebeproblemen: ein Ordner pro Vertrags-ID, Metadaten in DB.
- Wenn Standort/Abteilung eines Vertrags geändert wird, müssen Dateien konsistent bleiben oder kontrolliert verschoben werden.
- Zugriff darf immer nur über die App erfolgen, nicht direkt per Benutzer auf Dateisystempfade.
- Berechtigungsprüfung muss vor jedem Download greifen.

### Phase 6: Mailversand

- Mailversand an hinterlegte User-E-Mail-Adressen implementieren.
- Microsoft 365 Exchange Online als Anbieter berücksichtigen.
- Vor Umsetzung klären: SMTP AUTH erlaubt oder Microsoft Graph bevorzugt?
- Secrets für Mailversand lokal halten, nicht in GitHub.
- Reminder-/Benachrichtigungslogik für Vertragsfristen planen.

### Phase 7: CSV-Import

- Import bestehender Verträge per CSV implementieren.
- Standort-Kürzel in Standort-Tabelle ergänzen.
- CSV-Mapping definieren.
- Validierung und Fehlerprotokoll für Import bauen.
- Dublettenstrategie definieren.

### Phase 8: Ampellogik / Statuslogik

Aktueller Ist-Stand aus bisherigem Code/Review:

- Grau bei `terminated`, `ended`, `archived`.
- Überfällig bei negativer Restzeit.
- Rot bei <= 90 Tagen.
- Gelb bei <= 180 Tagen.
- Grün bei > 180 Tagen.

Offene Punkte:

- Ampellogik fachlich neu definieren.
- Admin-Oberfläche für Ampelgrenzen erstellen.
- Statusänderungen fachlich definieren.
- Automatische Status-/Ampelberechnung zentralisieren.

### Phase 9: Verantwortliche / Vertretungen

- Bei Vertragsanlage und Vertragsbearbeitung dürfen nur Benutzer aus dem jeweiligen Standort angezeigt werden.
- Es soll möglich sein, zusätzliche Verantwortliche / Vertretungen pro Vertrag zu hinterlegen.
- Dafür wahrscheinlich neue Zuordnungstabelle nötig, z. B. `contract_responsibles`.
- Berechtigungs- und Anzeigeverhalten fachlich klären.

### Phase 10: UI / Styling / Admin-Menü

- Nach Abschluss der Basis-/Sicherheitsarbeiten besseres Styling umsetzen.
- Stammdaten, Userverwaltung und Ampelverwaltung in eigenes Admin-Menü verschieben.
- Menüleiste für normale User entschlacken.
- Styling flexibel halten, damit neue Module einfach integrierbar sind.
- Dashboard soll für normale Nutzer direkt relevante kritische Verträge und Ampelübersicht zeigen.

## 10. Bekannte fachliche Anforderungen

- Nur berechtigte User dürfen Verträge sehen.
- Berechtigungen hängen an Standort/Abteilung/Rolle.
- Mehrfachauswahl soll nicht über STRG erfolgen, sondern über Checkboxen.
- Vertragsanlage soll Dokumentenupload direkt ermöglichen.
- Pflichtfelder in Vertragsverwaltung beachten.
- Dashboard soll keine unnötigen Filter enthalten, sondern relevante kritische Verträge und Ampelübersicht.
- Vertragsliste soll Filter übersichtlich und teilweise ausklappbar darstellen.

## 11. Empfohlener nächster Schritt im neuen Arbeitsbereich

1. Neuen Codex-Arbeitsbereich vom aktuellen GitHub `main` starten.
2. Diese Datei `PROJEKT_HANDOVER.md` als Kontext verwenden.
3. Zuerst prüfen, ob GitHub `main` die funktionierende `config/config.php` enthält.
4. Falls nicht: funktionierende Config sauber in einem neuen PR übernehmen.
5. Danach `scripts/verify_config.php` und `scripts/sanity_check.sh` ausführen.
6. Erst wenn GitHub, Codex und Server synchron sind, mit Phase 1 Codebasis/Security weiterarbeiten.
