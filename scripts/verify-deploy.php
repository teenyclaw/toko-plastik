<?php

/**
 * Standalone deploy checker — run on hosting without Laravel bootstrap:
 *   php scripts/verify-deploy.php
 */

$root = dirname(__DIR__);

$required = [
    'artisan',
    'composer.json',
    'composer.lock',
    'public/index.php',
    'bootstrap/app.php',
    'database/seeders/DatabaseSeeder.php',
    'routes/web.php',
    'routes/auth.php',
    'config/toko-plastik.php',
    'vendor/autoload.php',
    '.env',
];

$requiredDirs = [
    'app/Http/Controllers',
    'app/Models',
    'app/Services',
    'config',
    'database/migrations',
    'resources/views',
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
    'vendor',
];

echo "Toko Plastik deploy check — root: {$root}\n\n";

$missing = [];

foreach ($required as $path) {
    $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (! file_exists($full)) {
        $missing[] = $path;
        echo "[MISSING] {$path}\n";
    } else {
        echo "[OK]      {$path}\n";
    }
}

foreach ($requiredDirs as $dir) {
    $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
    if (! is_dir($full)) {
        $missing[] = $dir.'/';
        echo "[MISSING] {$dir}/\n";
    } else {
        echo "[OK]      {$dir}/\n";
    }
}

$env = @file_get_contents($root.'/.env') ?: '';
if ($env) {
    foreach (['APP_KEY=base64:', 'DB_DATABASE=', 'APP_URL='] as $needle) {
        if (! str_contains($env, $needle) || preg_match('/^'.preg_quote(rtrim($needle, '='), '/').'=\s*$/m', $env)) {
            $missing[] = ".env ({$needle} not set)";
            echo "[WARN]    .env — set {$needle}\n";
        }
    }
    if (preg_match('/^APP_DEBUG=true/m', $env)) {
        echo "[WARN]    APP_DEBUG=true — set false on production\n";
    }
    if (preg_match('/^APP_ENV=local/m', $env)) {
        echo "[WARN]    APP_ENV=local — use production on server\n";
    }
}

$migrationCount = count(glob($root.'/database/migrations/*.php') ?: []);
echo "\n[INFO]    {$migrationCount} migration file(s)\n";

echo "\n";
if ($missing === []) {
    echo "All critical paths present.\n";
    echo "Next: php scripts/diagnose-500.php\n";
    exit(0);
}

echo count($missing)." issue(s) found.\n\n";
echo "Common fixes:\n";
echo "  - vendor/ missing → composer install --no-dev -d {$root}\n";
echo "  - .env missing    → copy .env.example to .env\n";
echo "  - permissions     → chmod -R 775 storage bootstrap/cache\n";
exit(1);
