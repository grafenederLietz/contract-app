#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[1/3] Suche nach Merge-Konflikt-Markern ..."
if rg -n "^(<<<<<<<|=======|>>>>>>>)" -g '*.php' -g '*.css' -g '*.md' .; then
  echo "Fehler: Konflikt-Marker gefunden."
  exit 1
fi

echo "[2/3] PHP-Syntaxcheck ..."
while IFS= read -r file; do
  php -l "$file" >/dev/null
done < <(rg --files -g '*.php')

echo "[3/3] Schnelle Integritätschecks ..."
if [ "$(rg -n "function\s+app_abort\s*\(" config/config.php | wc -l)" -gt 1 ]; then
  echo "Fehler: app_abort() mehrfach in config/config.php gefunden."
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

echo "OK: Repository-Sanity-Check erfolgreich."
