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
 * Post-create-project welcome message.
 *
 * Printed as the final step of composer's `post-create-project-cmd` so a
 * fresh `composer create-project dixlase/dixlase-core` ends with clear
 * "next steps" guidance (build assets, start the server, open the wizard) —
 * mirroring the completion screen of the one-liner installer.
 *
 * Unlike the pre-built release ZIP, the composer source install ships no
 * compiled front-end assets (public/assets/build/ is a build artifact, not in
 * git), and the bundled theme's public link cannot be carried by a package.
 * scripts/post-create-build.php, which runs just before this, makes both
 * (#909). The steps below are printed only for what is still missing: when
 * npm was not found, the build failed, or DIXLASE_SKIP_BUILD was set.
 *
 * Framework-independent: runs before the app is bootable / configured.
 */
$dir = getcwd() ?: '.';

// Compiled Vite assets are absent on a source (composer) install but present
// when this somehow runs over a pre-built tree — only nudge to build when the
// manifest is missing, so the message stays accurate either way.
$assetsBuilt = is_file($dir.'/public/assets/build/manifest.json');

// The bundled theme ships its built assets; what a Composer package cannot
// carry is the public symlink core serves them through. Its absence means
// the link has not been made yet, not that the theme needs building.
$themeLinked = is_dir($dir.'/themes/DixlaseOnePage')
    ? file_exists($dir.'/public/assets/themes/DixlaseOnePage')
    : true;

// Emit ANSI colour only on an interactive terminal that has not opted out via
// the NO_COLOR convention (https://no-color.org). Otherwise print plain text
// so logs and redirected output stay clean.
$useColor = PHP_SAPI === 'cli'
    && function_exists('stream_isatty')
    && @stream_isatty(STDOUT)
    && getenv('NO_COLOR') === false;

$paint = static function (string $text, string $code) use ($useColor): string {
    return $useColor ? "\033[".$code.'m'.$text."\033[0m" : $text;
};
$bold = static fn (string $t): string => $paint($t, '1');
$green = static fn (string $t): string => $paint($t, '32');
$cyan = static fn (string $t): string => $paint($t, '36');
$yellow = static fn (string $t): string => $paint($t, '33');
$dim = static fn (string $t): string => $paint($t, '2');

$out = static fn (string $line = ''): int|false => fwrite(STDOUT, $line.PHP_EOL);

$out();
$out($bold($green('  ╔══════════════════════════════════════════╗')));
$out($bold($green('  ║')).$bold('      Installation Complete!              ').$bold($green('║')));
$out($bold($green('  ╚══════════════════════════════════════════╝')));
$out();
$out('  Dixlase has been installed to:');
$out('  '.$cyan($dir));
$out();
$out($bold('  Next steps:'));
$out();

$step = 1;

// Source installs (composer create-project) carry no compiled assets; without
// them the install wizard 500s on the missing Vite manifest. Build first.
if (! $assetsBuilt) {
    $out('  '.$step.'. Build the front-end assets:');
    $out('     '.$cyan('cd '.$dir.' && npm ci && npm run build'));
    $out();
    $step++;
}

// The front page and the login screen load the theme's bundle through
// public/assets/themes/<name>. The theme's own build is already in its
// resources/assets and is covered by its signature, so this only makes the
// link -- rebuilding would change the bytes the signature covers.
if (! $themeLinked) {
    $out('  '.$step.'. Expose the bundled theme\'s assets:');
    $out('     '.$cyan('php artisan dls:theme:symlink create DixlaseOnePage').'   (from the directory above)');
    $out();
    $step++;
}

// scripts/serve.php starts PHP's built-in server the way the install wizard
// needs (#909). `artisan serve` cannot run the wizard (#879): its .env
// watcher cuts off the request that wrote .env, and with --no-reload it
// keeps the start-up .env, so the wizard starts over after its last step.
// The server is left to the user: starting it here would keep
// create-project from returning.
$out('  '.$step.'. Start the local server:');
$out('     '.$cyan('cd '.$dir.' && php scripts/serve.php'));
$out('     Do not use `php artisan serve` for the wizard: it cannot see the .env the wizard writes.');
$out('     Restart this server after a core update or rollback, which recreates public/.');
$out();
$out('     Then open '.$cyan('http://127.0.0.1:8000').' in your browser.');
$out('     The '.$bold('Installation Wizard').' guides you through database, admin, and mail setup.');
$out();
$out('     The Database step starts with '.$bold('SQLite').', which needs no separate DB server. Choose MySQL there if you have one.');
$out();
$step++;
$out('  '.$step.'. For other deployment options (Docker, VPS, shared hosting), see:');
$out('     '.$cyan('https://docs.dixlase.com'));
$out();
$out($dim('  Documentation: https://docs.dixlase.com'));
$out($dim('  Support:       https://github.com/Dixlase/dixlase-core/issues'));
$out();
