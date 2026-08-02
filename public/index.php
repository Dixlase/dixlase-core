<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
//
// Round 5 residual fix: PHP-FPM workers hold a stale negative stat of
// the maintenance-mode sentinel path (realpath / stat cache) for
// several seconds after `artisan down` writes it. Without the
// invalidation below, workers keep telling `file_exists()` "no such
// file" for ~4-9 s and serve requests INTO the mid-swap source tree
// — producing the composer-autoload-layer `require(GlobalHelper.php):
// Failed to open stream` fatal the sandbox observed during the
// dryrun-11 → dryrun-12 verification. Clearing the stat cache for
// just this one path costs a single syscall per request in normal
// operation (negligible) and closes the entry-side gap: workers see
// the sentinel the instant it lands and short-circuit to 503 before
// any file operation runs.
$maintenance = __DIR__.'/../storage/framework/maintenance.php';
clearstatcache(true, $maintenance);
if (file_exists($maintenance)) {
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
