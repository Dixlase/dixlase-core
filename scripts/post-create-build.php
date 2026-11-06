<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
 * Build the front-end assets and link the bundled theme after
 * `composer create-project` (#909).
 *
 * Runs from composer's `post-create-project-cmd`, before the welcome message,
 * so a fresh install ends ready to serve. Each step runs only when its result
 * is missing, and the message that follows keeps showing whatever is still
 * left to do by hand.
 *
 * - Core's assets: `npm ci --ignore-scripts`, then `npm run build`. A Composer
 *   package carries no compiled assets (public/assets/build/ is not in git).
 *   The lockfile pins every package, and no dependency needs an install
 *   script to build.
 * - The bundled theme: only the public symlink core serves its assets
 *   through. The theme ships its own build, covered by its signature, so it
 *   is never rebuilt here.
 *
 * It never fails the create-project run: a step that cannot run, or fails,
 * prints a warning and is left to the message. Set DIXLASE_SKIP_BUILD=1 to
 * skip it entirely (create-project takes no options of our own), for
 * example when the assets are built somewhere else before a deploy.
 *
 * The server is not started here: that would keep create-project from
 * returning and take a port without asking. `php scripts/serve.php` does it.
 */
$dir = getcwd() ?: '.';

$skip = strtolower(trim((string) getenv('DIXLASE_SKIP_BUILD')));
if (in_array($skip, ['1', 'true', 'yes', 'on'], true)) {
    fwrite(STDOUT, '  DIXLASE_SKIP_BUILD is set: skipping the asset build and the theme link.'.PHP_EOL);

    exit(0);
}

$windows = PHP_OS_FAMILY === 'Windows';

$say = static fn (string $line): int|false => fwrite(STDOUT, '  '.$line.PHP_EOL);
$warn = static fn (string $line): int|false => fwrite(STDERR, '  WARNING: '.$line.PHP_EOL);

/**
 * Run a command in the project directory, streaming its output.
 * Returns its exit code.
 */
$run = static function (string $command) use ($dir): int {
    $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $dir);
    if (! is_resource($process)) {
        return 1;
    }

    return proc_close($process);
};

$onPath = static function (string $binary) use ($windows): bool {
    $probe = $windows
        ? 'where '.$binary.' >NUL 2>NUL'
        : 'command -v '.escapeshellarg($binary).' >/dev/null 2>&1';
    exec($probe, $output, $code);

    return $code === 0;
};

// 1. Core's front-end assets.
if (! is_file($dir.'/public/assets/build/manifest.json')) {
    if (! $onPath('npm')) {
        $warn('npm was not found, so the front-end assets were not built.');
        $warn('Install Node.js 20.19+ (or 22.12+) and run the build step shown below.');
    } else {
        $say('Building the front-end assets (npm ci, npm run build)...');
        $installed = $run('npm ci --ignore-scripts --no-audit --no-fund') === 0;
        $built = $installed && $run('npm run build') === 0;

        if (! $built || ! is_file($dir.'/public/assets/build/manifest.json')) {
            $warn('The front-end build did not finish. Run the build step shown below by hand.');
        }
    }
}

// 2. The bundled theme's public link. Only for a theme that ships its build;
// one that does not would need a build, which is not ours to make here.
$theme = 'DixlaseOnePage';
$themeDir = $dir.'/themes/'.$theme;
$link = $dir.'/public/assets/themes/'.$theme;

if (is_file($themeDir.'/resources/assets/manifest.json') && ! file_exists($link)) {
    $say('Linking the bundled theme\'s assets ('.$theme.')...');
    $command = escapeshellarg(PHP_BINARY).' artisan dls:theme:symlink create '.$theme.' --no-interaction';

    if ($run($command) !== 0 || ! file_exists($link)) {
        $warn('The theme link was not created. Run the theme step shown below by hand.');
    }
}

exit(0);
