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
 * Standalone script that generates composer.local.json. Invoked from
 * Composer's post-install-cmd / post-update-cmd, and by operators after a
 * manual plugin/theme clone. Runs without the Laravel framework.
 *
 * The generation itself lives in App\Support\ComposerLocalManifest, shared
 * with App\Helpers\ComposerLocalHelper::syncAutoload() so the two writers of
 * this file cannot drift apart. Both classes are plain PHP with no
 * dependencies, required by path because the framework autoloader does not
 * exist yet at post-install-cmd time.
 */
$baseDir = dirname(__DIR__);
$composerLocalPath = $baseDir.'/composer.local.json';

foreach (['ExtensionDirectories', 'ComposerLocalManifest'] as $class) {
    $path = $baseDir.'/app/Support/'.$class.'.php';

    if (! is_file($path)) {
        fwrite(STDERR, "sync-local-autoload: missing {$path}\n");
        fwrite(STDERR, "Refusing to write composer.local.json from a partial tree: a manifest\n");
        fwrite(STDERR, "generated without the shared rules would differ from the one the admin\n");
        fwrite(STDERR, "panel writes, and could register move-aside extension copies.\n");

        exit(1);
    }

    require_once $path;
}

$manifest = \App\Support\ComposerLocalManifest::build($baseDir);

file_put_contents($composerLocalPath, \App\Support\ComposerLocalManifest::encode($manifest));

$psr4Count = count($manifest['autoload']['psr-4'] ?? []);
$filesCount = count($manifest['autoload']['files'] ?? []);
$pluginCount = count(\App\Support\ComposerLocalManifest::detect($baseDir.'/plugins'));
$themeCount = count(\App\Support\ComposerLocalManifest::detect($baseDir.'/themes'));
// Plugins that are not enabled, as last recorded by core from the database
// (this script cannot read it). Their autoload.files are left out.
$withheldCount = count(array_intersect(
    \App\Support\ComposerLocalManifest::disabledPlugins($baseDir),
    \App\Support\ComposerLocalManifest::detect($baseDir.'/plugins')
));

echo "composer.local.json synced ({$pluginCount} plugins, {$themeCount} themes, {$filesCount} autoload files, {$psr4Count} psr-4 roots, {$withheldCount} disabled plugins' files withheld)\n";
