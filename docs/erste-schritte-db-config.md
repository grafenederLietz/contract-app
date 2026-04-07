# Erste Schritte: DB-Konfiguration einfach erklärt (für Nicht-Programmierer)

Diese Anleitung ist für **Schritt 1: App muss wieder laufen**.

## 1) Was bedeutet „Root“?
- In IIS ist bei dir die Site auf `C:\WebApps\contract-app\public` gebunden.
- Mit „Root“ meinte ich den **Projektordner** `C:\WebApps\contract-app`.
- Die Datei `config.php` liegt im Projektordner.
- Die Datei `config\config.php` liegt im Unterordner `config`.

## 2) Welche DB-Daten müssen korrekt sein?
In `config/config.php` sind diese Werte entscheidend:
- `$dbHost` = Datenbank-Server (z. B. `127.0.0.1` oder Server-IP)
- `$dbName` = Datenbankname (bei dir: `contractdb`)
- `$dbUser` = DB-Benutzer (z. B. `contractapp_user`)
- `$dbPass` = Passwort von genau diesem DB-Benutzer

Wenn **User/Passwort falsch** sind, funktioniert Login in der Web-App nicht (DB-Verbindung schlägt fehl).

## 3) Konkrete Empfehlung für dein Setup
Du hast in Screenshots MariaDB-User wie `contractapp_user` gezeigt.
Nutze deshalb:
- Host: `127.0.0.1` (wenn DB lokal am gleichen Server läuft)
- DB: `contractdb`
- User: `contractapp_user`
- Passwort: das Passwort dieses Users aus HeidiSQL/MariaDB

## 4) Ist Passwort im Klartext unsicher?
Kurz: **Ja, Klartext in Datei ist nicht ideal**.
Für den Start (internes System) ist es aber häufig so umgesetzt.

Besser (nächster Schritt nach Stabilisierung):
- Passwort als IIS-Umgebungsvariable `CONTRACTAPP_DB_PASS` setzen
- In `config/config.php` nur `getenv(...)` nutzen
- Rechte auf `config`-Ordner einschränken (nur Admin + IIS AppPool Identity)

## 5) Minimal-Checkliste (jetzt sofort)
1. `config/config.php` öffnen
2. Host/DB/User/Pass prüfen
3. IIS Site recyceln (oder `iisreset`)
4. `http://localhost:8080/login.php` testen
5. Falls Fehler bleibt: Inhalt aus `C:\WebApps\contract-app\logs\php-error.log` schicken

## 6) Wichtig
- Ich gehe mit dir **Schritt für Schritt**.
- Wir machen zuerst nur: „App startet + Login funktioniert stabil“.
- Danach erst Sicherheits-/Strukturverbesserungen.
