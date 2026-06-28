<?php

/**
 * Bootstrap Laravel and surface the first fatal error (for 500 debugging on hosting):
 *   php scripts/diagnose-500.php
 */

$root = dirname(__DIR__);

$checks = [
    'vendor/autoload.php',
    '.env',
    'bootstrap/app.php',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($checks as $path) {
    $full = $root . '/' . $path;
    $ok = str_ends_with($path, '.php') ? file_exists($full) : is_dir($full) || file_exists($full);
    echo ($ok ? '[OK] ' : '[MISSING] ') . $path . PHP_EOL;
    if (! $ok) {
        echo "Fix missing path before continuing.\n";
        exit(1);
    }
}

if (! is_writable($root . '/storage/logs')) {
    echo "[WARN] storage/logs is not writable — chmod 775 storage -R\n";
}

require $root . '/vendor/autoload.php';

$dotenv = $root . '/.env';
if (file_exists($dotenv)) {
    $env = file_get_contents($dotenv);

    if (! preg_match('/^APP_KEY=base64:.+/m', $env)) {
        echo "[FAIL] APP_KEY empty — run: php artisan key:generate\n";
    }
    if (preg_match('/^SESSION_DRIVER=database/m', $env)) {
        echo "[WARN] SESSION_DRIVER=database but project has no sessions migration — use SESSION_DRIVER=file\n";
    }
    if (preg_match('/^CACHE_STORE=database/m', $env)) {
        echo "[WARN] CACHE_STORE=database but project has no cache migration — use CACHE_STORE=file\n";
    }
}

try {
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "[OK] Database connection\n";

    if (config('session.driver') === 'database') {
        $has = Illuminate\Support\Facades\Schema::hasTable('sessions');
        if (! $has) {
            echo "[FAIL] SESSION_DRIVER=database but table `sessions` missing\n";
        }
    }

    if (config('cache.default') === 'database') {
        $has = Illuminate\Support\Facades\Schema::hasTable('cache');
        if (! $has) {
            echo "[FAIL] CACHE_STORE=database but table `cache` missing\n";
        }
    }

    echo "[OK] Laravel bootstrap successful\n";
} catch (Throwable $e) {
    echo "[EXCEPTION] " . $e::class . ': ' . $e->getMessage() . "\n";
    echo '  at ' . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
