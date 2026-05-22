<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Detect base path. Tries (in order): local dev, then common production folder names.
$basePath = null;
foreach ([__DIR__ . '/..', __DIR__ . '/../repositorie', __DIR__ . '/../repositorio'] as $candidate) {
    if (file_exists($candidate . '/vendor/autoload.php')) {
        $basePath = $candidate;
        break;
    }
}
if ($basePath === null) {
    http_response_code(500);
    exit('Laravel base path not found. Checked: project root, ../repositorie, ../repositorio.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
