<?php

/**
 * Bootstrap Laravel and surface the first fatal error (for 500 debugging on hosting):
 *   php scripts/diagnose-500.php
 */

$root = dirname(__DIR__);

function line(string $level, string $message): void
{
    echo "[{$level}] {$message}" . PHP_EOL;
}

function debugLog(string $hypothesisId, string $location, string $message, array $data = []): void
{
    $payload = [
        'sessionId' => '39f079',
        'hypothesisId' => $hypothesisId,
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) round(microtime(true) * 1000),
    ];

    $logPath = dirname(__DIR__) . '/storage/logs/deploy-debug-39f079.log';
    @file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
}

line('INFO', 'Toko Plastik — diagnose 500');
line('INFO', 'Root: ' . $root);
line('INFO', 'PHP: ' . PHP_VERSION);
// #region agent log
debugLog('H1-H5', 'diagnose-500.php:start', 'diagnose started', ['php' => PHP_VERSION, 'root' => $root]);
// #endregion

$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
foreach ($extensions as $ext) {
    $ok = extension_loaded($ext);
    line($ok ? 'OK' : 'FAIL', "PHP extension: {$ext}");
    if (! $ok) {
        // #region agent log
        debugLog('H4', 'diagnose-500.php:extensions', 'missing extension', ['extension' => $ext]);
        // #endregion
    }
}

line(function_exists('proc_open') ? 'OK' : 'WARN', 'proc_open: ' . (function_exists('proc_open') ? 'enabled' : 'disabled (use composer --no-scripts)'));

$checks = [
    'vendor/autoload.php',
    '.env',
    'bootstrap/app.php',
    'bootstrap/cache/packages.php',
    'public/index.php',
    'public/.htaccess',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($checks as $path) {
    $full = $root . '/' . $path;
    $ok = str_ends_with($path, '.php') || str_ends_with($path, '.htaccess')
        ? file_exists($full)
        : is_dir($full) || file_exists($full);
    line($ok ? 'OK' : 'MISSING', $path);
    if (! $ok) {
        // #region agent log
        debugLog('H1', 'diagnose-500.php:paths', 'missing path', ['path' => $path]);
        // #endregion
        line('FAIL', 'Fix missing path before continuing.');
        exit(1);
    }
}

foreach (['storage', 'storage/logs', 'storage/framework', 'storage/framework/sessions', 'storage/framework/views', 'storage/framework/cache', 'bootstrap/cache'] as $dir) {
    $full = $root . '/' . $dir;
    $writable = is_dir($full) && is_writable($full);
    line($writable ? 'OK' : 'WARN', "{$dir} writable: " . ($writable ? 'yes' : 'no'));
    if (! $writable) {
        // #region agent log
        debugLog('H2', 'diagnose-500.php:permissions', 'directory not writable', ['dir' => $dir]);
        // #endregion
    }
}

$env = file_get_contents($root . '/.env') ?: '';
$envChecks = [
    'APP_KEY' => '/^APP_KEY=base64:.+/m',
    'APP_URL' => '/^APP_URL=https?:\/\/.+/m',
    'DB_DATABASE' => '/^DB_DATABASE=\S+/m',
    'DB_USERNAME' => '/^DB_USERNAME=\S+/m',
];

foreach ($envChecks as $label => $pattern) {
    $ok = (bool) preg_match($pattern, $env);
    line($ok ? 'OK' : 'FAIL', ".env {$label}");
    if (! $ok) {
        // #region agent log
        debugLog('H3', 'diagnose-500.php:env', 'env value missing or invalid', ['key' => $label]);
        // #endregion
    }
}

if (preg_match('/^SESSION_DRIVER=database/m', $env)) {
    line('WARN', 'SESSION_DRIVER=database — use file on shared hosting unless sessions table exists');
}
if (preg_match('/^CACHE_STORE=database/m', $env)) {
    line('WARN', 'CACHE_STORE=database — use file on shared hosting unless cache table exists');
}
if (preg_match('/^APP_DEBUG=true/m', $env)) {
    line('WARN', 'APP_DEBUG=true on production exposes errors');
}

$cachedConfig = $root . '/bootstrap/cache/config.php';
if (file_exists($cachedConfig)) {
    line('INFO', 'bootstrap/cache/config.php exists (config was cached)');
    // #region agent log
    debugLog('H5', 'diagnose-500.php:config-cache', 'cached config present', ['path' => $cachedConfig]);
    // #endregion
}

$logFile = $root . '/storage/logs/laravel.log';
if (is_file($logFile)) {
    $tail = array_slice(file($logFile, FILE_IGNORE_NEW_LINES) ?: [], -8);
    if ($tail !== []) {
        line('INFO', 'Last laravel.log lines:');
        foreach ($tail as $logLine) {
            echo '  ' . $logLine . PHP_EOL;
        }
    }
} else {
    line('WARN', 'storage/logs/laravel.log not found yet');
}

require $root . '/vendor/autoload.php';

try {
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    line('OK', 'Laravel console bootstrap');

    Illuminate\Support\Facades\DB::connection()->getPdo();
    line('OK', 'Database connection');

    $hasUsers = Illuminate\Support\Facades\Schema::hasTable('users');
    line($hasUsers ? 'OK' : 'FAIL', 'Table users exists: ' . ($hasUsers ? 'yes' : 'no'));
    if (! $hasUsers) {
        // #region agent log
        debugLog('H3', 'diagnose-500.php:migrations', 'users table missing', []);
        // #endregion
        line('FAIL', 'Run: php artisan migrate --force');
    }

    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/up', 'GET');
    $response = $httpKernel->handle($request);
    line('OK', '/up HTTP status: ' . $response->getStatusCode());
    line('INFO', '/up body: ' . trim((string) $response->getContent()));
    $httpKernel->terminate($request, $response);

    // #region agent log
    debugLog('H1-H5', 'diagnose-500.php:success', 'diagnose completed', ['upStatus' => $response->getStatusCode()]);
    // #endregion

    line('OK', 'Diagnose finished — if browser still shows 500, check document root points to public/');
} catch (Throwable $e) {
    line('EXCEPTION', $e::class . ': ' . $e->getMessage());
    echo '  at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    // #region agent log
    debugLog('H1-H5', 'diagnose-500.php:exception', 'bootstrap failed', [
        'class' => $e::class,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    // #endregion
    exit(1);
}
