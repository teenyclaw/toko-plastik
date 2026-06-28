#!/usr/bin/env bash
# Quick recovery for generic HTTP 500 on shared hosting (cPanel).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Toko Plastik — fix hosting 500"
echo "Root: $ROOT"

if [[ ! -f .env ]]; then
  echo "ERROR: .env missing. Copy .env.example and configure first."
  exit 1
fi

if [[ ! -f vendor/autoload.php ]]; then
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
fi

composer dump-autoload --optimize --no-scripts 2>/dev/null || true

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan migrate --force --no-interaction
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "Done. Run: php scripts/diagnose-500.php"
echo "Then test: curl -s \${APP_URL:-https://toko-plastik.ylabsdev1980.com}/up"
