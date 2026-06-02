<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Show a friendly setup-required page when Composer dependencies have
// not been installed yet — the typical situation for someone who has
// just unzipped the source repo and opened the site before running
// `composer install`. Without this branch the next line surfaces a
// raw PHP fatal "Failed to open vendor/autoload.php" with no
// guidance. The fallback must stay self-contained — nothing under
// vendor/ has been autoloaded yet, so no Laravel classes are
// available to it.
if (! is_file(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/setup-required.php';
    exit;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
