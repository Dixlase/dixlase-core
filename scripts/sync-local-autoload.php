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
 * composer.local.json を自動生成するスタンドアロンスクリプト。
 * Composer の post-install-cmd / post-update-cmd から呼び出される。
 * Laravel フレームワーク非依存で動作する。
 */
$baseDir = dirname(__DIR__);
$composerLocalPath = $baseDir.'/composer.local.json';

// App\Support\ExtensionDirectories holds the canonical "is this an installed
// extension or a leftover copy of one" rule. This script runs from Composer's
// post-install-cmd, before the framework autoloader exists, so the class is
// required by path rather than autoloaded. It has no dependencies of its own.
// The inline fallback below keeps a partial tree from breaking the hook; it
// must stay in step with isInstalledName().
if (is_file($baseDir.'/app/Support/ExtensionDirectories.php')) {
    require_once $baseDir.'/app/Support/ExtensionDirectories.php';
}

/**
 * インストール済み拡張機能のディレクトリ名かどうかを判定
 *
 * Deploy tooling and operators move the previous version aside instead of
 * deleting it, leaving names like `DixlaseOnePage.stale.20260817-211837`
 * next to the live directory. Those copies are complete — manifest, `app/`,
 * `composer.json` — so a check for `app/` alone accepts them.
 */
function isInstalledExtensionName(string $entry): bool
{
    if (class_exists(\App\Support\ExtensionDirectories::class, false)) {
        return \App\Support\ExtensionDirectories::isInstalledName($entry);
    }

    if ($entry === '' || $entry === '.' || $entry === '..') {
        return false;
    }

    if (str_starts_with($entry, '.') || str_starts_with($entry, '_')) {
        return false;
    }

    return ! str_contains($entry, '.');
}

/**
 * 指定ディレクトリ内の拡張機能（プラグイン/テーマ）ディレクトリを検出
 *
 * Registering a move-aside copy here is not a cosmetic problem. Each entry's
 * `autoload.files` is merged into composer.local.json, and Composer `require`s
 * those files unconditionally at bootstrap — so deleting a copy that was
 * registered takes down every request and every artisan command with
 * "Failed opening required ...", a white screen rather than a degraded
 * extension. Brand hit exactly this on 2026-09-23 when a deploy change pruned
 * old `themes/*.stale.*` directories, and stayed up only because OPcache still
 * held the compiled autoload file. Hence two independent guards below: the
 * name rule, and a manifest requirement.
 *
 * @param  string  $parentDir  plugins/ または themes/ のフルパス
 * @return array<string> ディレクトリ名の配列
 */
function detectExtensionDirectories(string $parentDir): array
{
    if (! is_dir($parentDir)) {
        return [];
    }

    $names = [];
    foreach (scandir($parentDir) as $entry) {
        if (! isInstalledExtensionName($entry)) {
            continue;
        }

        $dir = $parentDir.'/'.$entry;
        if (! is_dir($dir) || ! is_dir($dir.'/app')) {
            continue;
        }

        // An installed extension always declares itself. This also skips a
        // bare directory that merely happens to contain an `app/`.
        if (! is_file($dir.'/plugin.json') && ! is_file($dir.'/theme.json')) {
            continue;
        }

        $names[] = $entry;
    }

    sort($names);

    return $names;
}

/**
 * Read an extension's composer.json `autoload.files` entries and return
 * them rebased to paths relative to the Core root, so they can be merged
 * into composer.local.json's flat `autoload.files` list.
 *
 * Returns an empty array when the file is missing, unreadable, or has no
 * `autoload.files` section. Composer tolerates the absence of the file
 * silently — only well-formed extensions contribute entries.
 *
 * @param  string  $extensionDir  e.g. plugins/DixlaseSEO or themes/Foo
 * @param  string  $relPrefix  same string with trailing slash, prefixed
 *                             onto each file path so the autoload list
 *                             stays relative to the Core base dir
 * @return array<int, string>
 */
function readExtensionAutoloadFiles(string $extensionDir, string $relPrefix): array
{
    $composerJson = $extensionDir.'/composer.json';
    if (! is_file($composerJson) || ! is_readable($composerJson)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($composerJson), true);
    if (! is_array($decoded)) {
        return [];
    }

    $files = $decoded['autoload']['files'] ?? [];
    if (! is_array($files)) {
        return [];
    }

    $result = [];
    foreach ($files as $entry) {
        if (! is_string($entry) || $entry === '') {
            continue;
        }
        $result[] = $relPrefix.ltrim($entry, '/');
    }

    return $result;
}

// プラグインとテーマを検出
$plugins = detectExtensionDirectories($baseDir.'/plugins');
$themes = detectExtensionDirectories($baseDir.'/themes');

// PSR-4 マッピングを構築
$autoload = [];

// Files autoload (e.g. global helper functions a plugin declares in its
// composer.json under autoload.files). PSR-4 cannot autoload non-class
// symbols, so plugins that ship `function dls_*_*()` helpers must list
// the helper file here and we hoist that into composer.local.json so
// the function is available even before the plugin's ServiceProvider
// boots (e.g. inside an isolated unit test).
$files = [];

// Custom\Plugins\{P}\App\ and Custom\Themes\{T}\App\ are reserved
// for the deferred plugin/theme logic-override loader -- see
// .backlog/custom-overrides-plugin-theme.md for the design.
// The PSR-4 mapping is emitted unconditionally so that once the
// loader trait lands, user code already written under those
// namespaces will autoload without a second composer.local.json
// migration. Composer tolerates missing target directories.
foreach ($plugins as $name) {
    $autoload["Plugins\\{$name}\\App\\"] = "plugins/{$name}/app";
    $autoload["Plugins\\{$name}\\Database\\Factories\\"] = "plugins/{$name}/database/factories";
    $autoload["Plugins\\{$name}\\Database\\Seeders\\"] = "plugins/{$name}/database/seeders";
    $autoload["Plugins\\{$name}\\Tests\\"] = "plugins/{$name}/tests";
    $autoload["Custom\\Plugins\\{$name}\\App\\"] = "custom/plugins/{$name}/app";

    foreach (readExtensionAutoloadFiles($baseDir."/plugins/{$name}", "plugins/{$name}/") as $relPath) {
        $files[] = $relPath;
    }
}

foreach ($themes as $name) {
    $autoload["Themes\\{$name}\\App\\"] = "themes/{$name}/app";
    $autoload["Themes\\{$name}\\Database\\Factories\\"] = "themes/{$name}/database/factories";
    $autoload["Themes\\{$name}\\Database\\Seeders\\"] = "themes/{$name}/database/seeders";
    $autoload["Custom\\Themes\\{$name}\\App\\"] = "custom/themes/{$name}/app";

    foreach (readExtensionAutoloadFiles($baseDir."/themes/{$name}", "themes/{$name}/") as $relPath) {
        $files[] = $relPath;
    }
}

// Preserve insertion order but drop duplicates so two plugins listing
// the same shared bootstrap file do not double-require it.
$files = array_values(array_unique($files));

// composer.local.json を生成
$autoloadSection = [
    'psr-4' => $autoload,
];
if ($files !== []) {
    $autoloadSection['files'] = $files;
}

$composerLocal = [
    '_comment' => 'このファイルは自動生成されます。手動で編集しないでください。',
    '_generated_at' => date('Y-m-d H:i:s'),
    'autoload' => $autoloadSection,
];

$json = json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents($composerLocalPath, $json."\n");

$pluginCount = count($plugins);
$themeCount = count($themes);
$filesCount = count($files);
echo "composer.local.json synced ({$pluginCount} plugins, {$themeCount} themes, {$filesCount} autoload files)\n";
