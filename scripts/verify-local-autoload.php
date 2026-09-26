<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Standalone script that checks the dumped autoloader actually resolves the
 * extension PSR-4 roots composer.local.json declares, and dumps once more
 * when it does not. Invoked from Composer's post-autoload-dump, and safe to
 * run by hand. Runs without the Laravel framework.
 *
 * Why it is needed: composer-merge-plugin reads composer.local.json when
 * Composer initialises, which is before core's pre-autoload-dump hook writes
 * it. A tree where the file does not exist yet -- a release ZIP, which ships
 * without it because the file is generated and gitignored -- therefore dumps
 * an autoloader with no extension roots, and nothing dumps again. The bundled
 * theme's ServiceProvider is then unresolvable and the front page is a 500
 * (v0.3.55, Docker installer verification 2026-09-27).
 */
$baseDir = dirname(__DIR__);

// The second dump is run with --no-scripts, so it cannot re-enter this
// script. The flag is a second guard, and lets an operator opt out.
if ((getenv('DIXLASE_AUTOLOAD_REDUMP') ?: '') === '1') {
    exit(0);
}

foreach (['ExtensionDirectories', 'ComposerLocalManifest'] as $class) {
    $path = $baseDir.'/app/Support/'.$class.'.php';

    if (! is_file($path)) {
        // A partial tree is the sync script's problem to report, not this
        // one's: there is nothing to verify against.
        exit(0);
    }

    require_once $path;
}

$missing = \App\Support\ComposerLocalManifest::missingPsr4Roots($baseDir);

if ($missing === []) {
    exit(0);
}

echo 'Autoload map is missing '.count($missing).' extension PSR-4 root(s): '
    .implode(', ', array_slice($missing, 0, 4))
    .(count($missing) > 4 ? ', …' : '').'
';

$composer = getenv('COMPOSER_BINARY') ?: '';

if (! is_string($composer) || $composer === '' || ! is_file($composer)) {
    fwrite(STDERR, 'Could not locate composer; run `composer dump-autoload` to finish the install.
');
    exit(0);
}

// Composer runs its scripts under the CLI SAPI, so PHP_BINARY is an
// interpreter here (unlike a web request, where it is php-fpm).
$php = PHP_SAPI === 'cli' ? PHP_BINARY : 'php';

$command = escapeshellarg($php).' '.escapeshellarg($composer)
    .' dump-autoload --optimize --no-scripts --no-interaction';

$output = [];
$exitCode = 0;
exec('cd '.escapeshellarg($baseDir).' && DIXLASE_AUTOLOAD_REDUMP=1 '.$command.' 2>&1', $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Re-dump failed (exit {$exitCode}):
".implode('
', array_slice($output, -10)).'
');
    fwrite(STDERR, 'Run `composer dump-autoload` to finish the install.
');
    exit(0);
}

$stillMissing = \App\Support\ComposerLocalManifest::missingPsr4Roots($baseDir);

echo $stillMissing === []
    ? 'Autoload map rebuilt with the extension roots.
'
    : 'Autoload map still missing '.count($stillMissing).' root(s) after a re-dump.
';
