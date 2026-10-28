<?php

declare(strict_types=1);

$root = __DIR__;
if (!is_file($root . '/bootstrap/app.php') || !is_file($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "Run this script from the Laravel project root (with vendor installed).\n");
    exit(2);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$issues = 0;
$warnings = 0;
$report = static function (string $status, string $message) use (&$issues, &$warnings): void {
    if ($status === 'FAIL') { ++$issues; }
    if ($status === 'WARN') { ++$warnings; }
    echo '[' . $status . '] ' . $message . PHP_EOL;
};

$env = (string) config('app.env', 'unknown');
$debug = (bool) config('app.debug');
$local = in_array($env, ['local', 'testing'], true);
echo 'Laravel configuration audit' . PHP_EOL;
echo 'Environment: ' . $env . PHP_EOL;
$report($debug && !$local ? 'FAIL' : ($debug ? 'WARN' : 'OK'), $debug ? 'APP_DEBUG enabled; disable for demonstrations on shared/public networks.' : 'APP_DEBUG disabled.');
$report(trim((string) config('app.key', '')) === '' ? 'FAIL' : 'OK', 'Application key ' . (trim((string) config('app.key', '')) === '' ? 'missing.' : 'configured (value hidden).'));
$report($env === 'production' ? 'OK' : 'WARN', 'APP_ENV=' . $env . '; local is fine for development, use production for deployment.');
$report((bool) config('session.http_only', false) ? 'OK' : 'FAIL', 'Session cookie HttpOnly=' . ((bool) config('session.http_only', false) ? 'true' : 'false'));
$sameSite = strtolower((string) config('session.same_site', ''));
$report(in_array($sameSite, ['lax', 'strict'], true) ? 'OK' : 'WARN', 'Session SameSite=' . ($sameSite ?: 'unset'));
$secure = (bool) config('session.secure', false);
$report($secure ? 'OK' : 'WARN', 'Session Secure=' . ($secure ? 'true' : 'false') . '; required for public HTTPS deployment, usually false for local HTTP.');
$disk = (string) config('filesystems.default', 'unknown');
echo '[INFO] Default filesystem disk: ' . $disk . PHP_EOL;
$report(is_file($root . '/public/.env') ? 'FAIL' : 'OK', 'No .env file directly inside public/.');
$report(is_dir($root . '/public/vendor') ? 'WARN' : 'OK', 'Public vendor/ directory ' . (is_dir($root . '/public/vendor') ? 'exists; review contents.' : 'absent.'));
$report(is_file($root . '/public/composer.json') ? 'WARN' : 'OK', 'Composer manifest ' . (is_file($root . '/public/composer.json') ? 'found under public/.' : 'not exposed under public/.'));
$gitignore = is_file($root . '/.gitignore') ? (string) file_get_contents($root . '/.gitignore') : '';
$report(str_contains($gitignore, '.env') ? 'OK' : 'WARN', '.gitignore ' . (str_contains($gitignore, '.env') ? 'mentions .env.' : 'does not explicitly mention .env.'));
$routes = app('router')->getRoutes();
$storagePut = [];
foreach ($routes as $route) {
    if (in_array('PUT', $route->methods(), true) && str_starts_with($route->uri(), 'storage/')) {
        $storagePut[] = $route->uri();
    }
}
$report($storagePut ? 'WARN' : 'OK', $storagePut ? 'PUT storage route registered (' . implode(', ', $storagePut) . '); verify local storage serving is disabled outside local environment.' : 'No PUT storage route registered.');
echo 'Results: ' . $issues . ' failure(s), ' . $warnings . ' warning(s).' . PHP_EOL;
exit($issues > 0 ? 1 : 0);
