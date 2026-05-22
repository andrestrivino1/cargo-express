<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Detect base path:
// - Local dev: public/ lives inside the project root → ../
// - Production (GoDaddy shared hosting): public_html/ is sibling of repositorio/ → ../repositorio/
$basePath = file_exists(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/..'
    : __DIR__ . '/../repositorio';

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
