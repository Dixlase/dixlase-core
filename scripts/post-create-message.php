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
 * compiled front-end assets (public/build/ is a build artifact, not in git),
 * so the npm build step is surfaced unless those assets already exist.
 *
 * Framework-independent: runs before the app is bootable / configured.
 */
$dir = getcwd() ?: '.';

// Compiled Vite assets are absent on a source (composer) install but present
// when this somehow runs over a pre-built tree — only nudge to build when the
// manifest is missing, so the message stays accurate either way.
$assetsBuilt = is_file($dir.'/public/build/manifest.json');

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
    $out('     '.$cyan('cd '.$dir.' && npm install && npm run build'));
    $out();
    $step++;
}

$out('  '.$step.'. Start the local server:');
if ($assetsBuilt) {
    $out('     '.$cyan('cd '.$dir.' && php artisan serve'));
} else {
    $out('     '.$cyan('php artisan serve').'   (from the directory above)');
}
$out();
$out('     Then open '.$cyan('http://127.0.0.1:8000').' in your browser.');
$out('     The '.$bold('Installation Wizard').' guides you through database, admin, and mail setup.');
$out();
$out('     '.$yellow('No MySQL?').' Choose '.$bold('SQLite').' in the Database step to start without a separate DB server.');
$out();
$step++;
$out('  '.$step.'. For other deployment options (Docker, VPS, shared hosting), see:');
$out('     '.$cyan('https://docs.dixlase.com'));
$out();
$out($dim('  Documentation: https://docs.dixlase.com'));
$out($dim('  Support:       https://github.com/Dixlase/dixlase-core/issues'));
$out();
