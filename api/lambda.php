<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// On Vercel, all requests hit this handler. Serve static assets from public/ before booting Laravel
// so that /js/*.js, /css/*.css, /build/*, favicon.ico etc. are returned correctly.
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = $path === '' || $path === false ? '/' : $path;
$publicDir = __DIR__ . '/../public';
$staticPrefixes = ['/js/', '/css/', '/build/', '/favicon.ico', '/robots.txt'];
$isStatic = false;
foreach ($staticPrefixes as $prefix) {
    if ($prefix === $path || str_starts_with($path, $prefix)) {
        $isStatic = true;
        break;
    }
}
if ($isStatic) {
    $file = $publicDir . $path;
    if ($path === '/') {
        $file = $publicDir . '/index.php';
    } elseif (is_file($file) && is_readable($file)) {
        $resolved = realpath($file);
        $publicReal = realpath($publicDir);
        if ($resolved === false || $publicReal === false || !str_starts_with($resolved, $publicReal)) {
            // Path traversal or invalid path - fall through to Laravel
        } else {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $types = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'txt' => 'text/plain',
            'json' => 'application/json',
        ];
            $mime = $types[$ext] ?? 'application/octet-stream';
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($file);
            return;
        }
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
