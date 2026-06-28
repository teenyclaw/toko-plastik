#!/usr/bin/env bash
# Run ON the production server (SSH / cPanel Terminal) from project root.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Toko Plastik — production deploy"
echo "Root: $ROOT"

if [[ ! -f .env ]]; then
  echo "ERROR: .env not found. Copy .env.example and configure first."
  exit 1
fi

if [[ ! -f vendor/autoload.php ]]; then
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
  elif [[ -f ~/composer.phar ]]; then
    php ~/composer.phar install --no-dev --optimize-autoloader --no-interaction --no-scripts
  else
    echo "ERROR: vendor/ missing and composer not found."
    exit 1
  fi
  composer dump-autoload --optimize --no-scripts 2>/dev/null || true
fi

if [[ ! -f bootstrap/cache/packages.php ]]; then
  php artisan package:discover --ansi || true
fi

php artisan migrate --force --no-interaction
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo ""
echo "Done. Test: curl -s \${APP_URL:-http://localhost}/up"
echo "Run: php scripts/diagnose-500.php"
