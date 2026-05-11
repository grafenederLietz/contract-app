# Smoke-Test-Checkliste – Contract App

Stand: 2026-05-11

Diese Checkliste ist der nächste Schritt nach einem erfolgreichen GitHub-Merge und Server-Sync. Sie dient dazu, vor weiteren Feature- oder Security-Änderungen zu bestätigen, dass der aktuelle `main`-Stand auf dem IIS-Server lauffähig ist.

## 1. Ziel

Ein Smoke-Test ist kein vollständiger Fachtest. Er prüft nur, ob die wichtigsten Wege der Anwendung nach Deployment grundsätzlich funktionieren:

- keine PHP-Parse-Errors oder sichtbaren Fatal Errors,
- Login/Logout funktioniert,
- Dashboard und Kernseiten sind erreichbar,
- Verträge können angezeigt, angelegt und bearbeitet werden,
- Upload und Download funktionieren,
- Rollen-/Standort-/Abteilungsrechte greifen grob plausibel,
- Ampel-/Fristenübersicht ist sichtbar.

## 2. Voraussetzungen

### 2.1 Git-Stand auf dem Server

Auf dem Server ausführen:

```powershell
cd C:\WebApps\contract-app
git checkout main
git pull origin main
git log -1 --oneline
git status
```

Erwartung:

- Branch ist `main`.
- `git pull` meldet den aktuellen Stand.
- `git status` meldet `nothing to commit, working tree clean`.
- `git log -1 --oneline` zeigt den erwarteten Merge-Commit.

### 2.2 Config-/PHP-Prüfung

Auf dem Server ausführen:

```powershell
C:\PHP\php.exe -l C:\WebApps\contract-app\config\config.php
C:\PHP\php.exe C:\WebApps\contract-app\scripts\verify_config.php
iisreset
```

Erwartung:

- `php -l` meldet `No syntax errors detected`.
- `verify_config.php` meldet `OK: config/config.php ist sauber.`
- Eine bekannte `pdo_firebird`-Startup-Warnung ist derzeit dokumentiert und blockiert den Smoke-Test nicht, solange die beiden Prüfungen erfolgreich sind.
- IIS startet nach `iisreset` wieder erfolgreich.

### 2.3 Lokale Secrets

Prüfen:

- `C:\WebApps\contract-app\config\local.php` existiert auf dem Server.
- Die Datei enthält echte lokale DB-Zugangsdaten.
- Die Datei ist nicht in Git getrackt.

## 3. Testdaten vorbereiten

Für den Smoke-Test sollten mindestens vorhanden sein:

- ein Admin-Benutzer,
- ein normaler Benutzer mit eingeschränktem Standort-/Abteilungszugriff,
- mindestens ein Standort,
- mindestens eine Abteilung,
- mindestens ein bestehender Vertrag,
- eine kleine Testdatei für Uploads, z. B. ein PDF unter 1 MB.

Empfehlung:

- Keine echten sensiblen Vertragsdokumente für den Smoke-Test verwenden.
- Testvertrag klar kennzeichnen, z. B. `SMOKE TEST - bitte löschen`.

## 4. Browser-Smoke-Test

| Nr. | Bereich | Schritte | Erwartetes Ergebnis | Ergebnis |
| --- | --- | --- | --- | --- |
| 1 | Startseite | IIS-URL der App im Browser öffnen. | App lädt ohne PHP-/IIS-Fehler. | ☐ OK / ☐ Fehler |
| 2 | Login leer | Login ohne Benutzername/Passwort absenden. | Validierungsmeldung erscheint, kein Fatal Error. | ☐ OK / ☐ Fehler |
| 3 | Login falsch | Mit falschen Zugangsdaten anmelden. | Login wird abgelehnt, keine Detailinfos über DB/System. | ☐ OK / ☐ Fehler |
| 4 | Login Admin | Mit Admin-Benutzer anmelden. | Weiterleitung zum Dashboard funktioniert. | ☐ OK / ☐ Fehler |
| 5 | Dashboard | Dashboard öffnen. | Ampel-/Übersichtsbereich sichtbar, keine PHP-Fehler. | ☐ OK / ☐ Fehler |
| 6 | Vertragsliste | Vertragsübersicht öffnen. | Liste lädt, Filterbereich ist sichtbar und bedienbar. | ☐ OK / ☐ Fehler |
| 7 | Vertragsdetails/Bearbeiten | Bestehenden Vertrag öffnen/bearbeiten. | Formular lädt, bestehende Daten sind sichtbar. | ☐ OK / ☐ Fehler |
| 8 | Vertragsanlage | Neuen Testvertrag mit Pflichtfeldern anlegen. | Vertrag wird gespeichert oder fachliche Validierung erscheint. | ☐ OK / ☐ Fehler |
| 9 | Upload | Beim Testvertrag ein PDF/DOC/DOCX hochladen. | Upload wird akzeptiert, Datei ist dem Vertrag zugeordnet. | ☐ OK / ☐ Fehler |
| 10 | Download | Hochgeladene Datei herunterladen. | Download startet als Datei, kein direkter Pfad wird angezeigt. | ☐ OK / ☐ Fehler |
| 11 | Benutzerverwaltung | Benutzerliste als Admin öffnen. | Liste lädt ohne SQL-/PHP-Fehler. | ☐ OK / ☐ Fehler |
| 12 | Benutzer bearbeiten | Test-/Normalbenutzer öffnen. | Formular lädt inkl. Rollen/Standorte/Abteilungen. | ☐ OK / ☐ Fehler |
| 13 | Standortverwaltung | Standortliste öffnen. | Liste lädt ohne SQL-/PHP-Fehler. | ☐ OK / ☐ Fehler |
| 14 | Abteilungsverwaltung | Abteilungsliste öffnen. | Liste lädt ohne SQL-/PHP-Fehler. | ☐ OK / ☐ Fehler |
| 15 | Logout | Logout klicken. | Session wird beendet, Login-Seite erscheint. | ☐ OK / ☐ Fehler |
| 16 | Zugriff nach Logout | Geschützte Seite direkt per URL öffnen. | Weiterleitung/Blockierung durch Login-Schutz. | ☐ OK / ☐ Fehler |
| 17 | Login Normalnutzer | Mit eingeschränktem Benutzer anmelden. | Dashboard/Verträge laden nur mit erlaubtem Zugriff. | ☐ OK / ☐ Fehler |
| 18 | Rechteprüfung Vertrag | Als Normalnutzer einen nicht berechtigten Vertrag direkt per URL öffnen. | Zugriff wird verweigert, keine Details werden angezeigt. | ☐ OK / ☐ Fehler |
| 19 | Admin-only Seiten | Als Normalnutzer Benutzer-/Standort-/Abteilungsverwaltung öffnen. | Zugriff wird verweigert. | ☐ OK / ☐ Fehler |
| 20 | Layout/CSS | Mehrere Seiten ansehen. | Styling über `/app.css` ist sichtbar, Seiten sind nutzbar. | ☐ OK / ☐ Fehler |

## 5. Go/No-Go-Kriterien

### Go

Weiter mit der nächsten technischen Aufgabe, wenn alle Punkte erfüllt sind:

- keine PHP-Fatal- oder Parse-Errors im Browser,
- Login/Logout funktioniert,
- Dashboard und Vertragsliste laden,
- Upload und Download funktionieren,
- Admin-Stammdatenbereiche laden,
- eingeschränkter Benutzer sieht nur zulässige Inhalte,
- `config/config.php` und `scripts/verify_config.php` sind auf dem Server OK.

### No-Go

Nicht mit neuen Features weiterarbeiten, wenn einer dieser Punkte auftritt:

- PHP-Parse-Error oder Fatal Error,
- Login nicht möglich,
- Datenbankverbindung fehlerhaft,
- Upload oder Download grundsätzlich defekt,
- Normalnutzer kann Adminseiten oder nicht berechtigte Verträge sehen,
- Git-Working-Tree auf dem Server ist nicht sauber.

## 6. Fehlerprotokoll

Bei Fehlern bitte pro Fund dokumentieren:

```text
Datum/Uhrzeit:
Tester:
Server-Commit aus git log -1:
Benutzer/Rolle:
URL/Seite:
Schritte zur Reproduktion:
Erwartetes Ergebnis:
Tatsächliches Ergebnis:
Browser-Fehlermeldung:
PHP-/IIS-Logauszug:
Screenshot vorhanden: ja/nein
Priorität: Blocker / Hoch / Mittel / Niedrig
```

## 7. Nach erfolgreichem Smoke-Test

Wenn der Smoke-Test grün ist, ist der nächste technische PR empfohlen:

1. Upload-Validierung in einen gemeinsamen Helper auslagern.
2. Upload-Regeln für `contract_create.php` und `contract_edit.php` angleichen.
3. Danach Rechte-/Autorisierungsreview für Verträge und Downloads fortsetzen.

Damit wird direkt an die offene Security-Review-Maßnahme `Upload-Prüfungen in gemeinsame Helper-Funktion auslagern` angeschlossen.

## 8. Ergebnis aktueller Smoke-Test

Status am 2026-05-11: **erfolgreich abgeschlossen**.

- Server-`main` war laut Rückmeldung sauber mit `origin/main` synchronisiert.
- `config/config.php` hatte keine Syntaxfehler.
- `scripts/verify_config.php` meldete `OK: config/config.php ist sauber.`
- IIS wurde erfolgreich neu gestartet.
- Alle Browser-Smoke-Testpunkte wurden erfolgreich geprüft.
- Der zwischenzeitliche Upload-Blocker bei der Vertragsanlage (`Upload mime invalid:` mit leerem MIME-Wert) wurde behoben und der Vertrag konnte anschließend erfolgreich gespeichert werden.

Nächster empfohlener Arbeitsschritt: Upload-Validierung in einen gemeinsamen Helper auslagern und die Regeln zwischen Vertragsanlage und Vertragsbearbeitung vereinheitlichen.
