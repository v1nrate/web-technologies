<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = Illuminate\Support\Facades\Route::getRoutes();
$names = [];
$issues = [];
$count = 0;

foreach ($routes as $route) {
    $count++;
    $name = $route->getName();
    if ($name !== null) {
        if (isset($names[$name])) {
            $issues[] = "Duplicate route name: {$name}";
        }
        $names[$name] = true;
    }
    $action = $route->getActionName();
    if ($action !== 'Closure' && str_contains($action, '@')) {
        [$class, $method] = explode('@', $action, 2);
        if (!class_exists($class) || !method_exists($class, $method)) {
            $issues[] = "Missing controller action: {$action}";
        }
    }
}

$directory = __DIR__ . '/resources/views';
$checked = 0;
if (is_dir($directory)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        $checked++;
        $content = file_get_contents($file->getPathname());
        preg_match_all('/\broute\s*\(\s*[\'\"]([^\'\"]+)[\'\"]/', $content, $matches);
        foreach (array_unique($matches[1]) as $name) {
            if (!isset($names[$name])) {
                $relative = substr($file->getPathname(), strlen(__DIR__) + 1);
                $issues[] = "Undefined route in {$relative}: {$name}";
            }
        }
    }
}

echo "Routes: {$count}\nBlade templates: {$checked}\nIssues: " . count($issues) . "\n";
foreach ($issues as $issue) {
    echo "[ISSUE] {$issue}\n";
}
if ($issues) {
    exit(1);
}
echo "[OK] Static route checks passed.\n";
