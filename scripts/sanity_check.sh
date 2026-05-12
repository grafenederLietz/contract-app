#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[1/6] Suche nach Merge-Konflikt-Markern ..."
if rg -n "^(<<<<<<<|=======|>>>>>>>)" --glob '!.git/**' --glob '!vendor/**' --glob '!Uploads/**' .; then
  echo "Fehler: Konflikt-Marker gefunden."
  exit 1
fi

echo "[2/6] PHP-Syntaxcheck ..."
while IFS= read -r file; do
  php -l "$file" >/dev/null
done < <(rg --files -g '*.php' -g '!vendor/**')

echo "[3/6] Config-Integritätscheck ..."
php scripts/verify_config.php

if rg -n "^function .*[)]\s*$" config/config.php >/dev/null; then
  echo "Fehler: Funktions-Deklarationen in config/config.php müssen die öffnende Klammer auf derselben Zeile haben."
  exit 1
fi

if [ "$(rg -n "function\s+app_abort\s*\(" config/config.php | wc -l)" -gt 1 ]; then
  echo "Fehler: app_abort() mehrfach in config/config.php gefunden."
  exit 1
fi

if rg -n "function\s+app_abort\([^)]*\)\s*:\s*void\s*$" config/config.php >/dev/null \
  && rg -n "function\s+app_abort\([^)]*\)\s*:\s*never\s*$" config/config.php >/dev/null; then
  echo "Fehler: app_abort() doppelt (void/never) in config/config.php gefunden."
  exit 1
fi

if rg -n "\$dbPass\s*=\s*['\"][^'\"]+['\"]\s*;" config/config.php >/dev/null; then
  echo "Fehler: Klartext-Passwortzuweisung (\$dbPass = '...') in config/config.php gefunden."
  exit 1
fi

if rg -n "getenv\\('CONTRACTAPP_DB_" config/config.php >/dev/null; then
  echo "Fehler: DB-Credentials dürfen nicht aus ENV gelesen werden (nur config/local.php erlaubt)."
  exit 1
fi

if rg -n "\\\\n" config/config.php >/dev/null; then
  echo "Warnung: Literal \\n in config/config.php gefunden (möglicher fehlerhafter Copy/Paste-Stand)."
fi

echo "[4/6] Prüfung auf direkte PHP-Abbrüche ..."
if rg -n "\\bdie\\s*\\(" config public src scripts --glob '*.php'; then
  echo "Fehler: Direkte die(...)-Abbrüche gefunden. Bitte app_abort(...) verwenden."
  exit 1
fi


echo "[5/6] Prüfung auf leere PHP-Dateien ..."
empty_php_files="$(find config public scripts src -type f -name '*.php' -size 0 -print)"
if [ -n "$empty_php_files" ]; then
  echo "Fehler: Leere PHP-Dateien gefunden:"
  echo "$empty_php_files"
  exit 1
fi

echo "[6/6] Prüfung auf alte Inline-Upload-Fragmente ..."
if rg -n "(uploadBasePath|allowedMimeByExt|finfo_open|mime_content_type|CONTRACT_MAX_UPLOAD_BYTES)" public/contract_create.php public/contract_edit.php; then
  echo "Fehler: Alte Inline-Upload-Fragmente gefunden. Upload-Logik muss in src/upload.php bleiben."
  exit 1
fi

echo "OK: Repository-Sanity-Check erfolgreich."
