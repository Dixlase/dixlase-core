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

/**
 * 指定ディレクトリ内の拡張機能（プラグイン/テーマ）ディレクトリを検出
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
        if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
            continue;
        }
        $dir = $parentDir.'/'.$entry;
        if (is_dir($dir) && is_dir($dir.'/app')) {
            $names[] = $entry;
        }
    }

    sort($names);

    return $names;
}

// プラグインとテーマを検出
$plugins = detectExtensionDirectories($baseDir.'/plugins');
$themes = detectExtensionDirectories($baseDir.'/themes');

// PSR-4 マッピングを構築
$autoload = [];

foreach ($plugins as $name) {
    $autoload["Plugins\\{$name}\\App\\"] = "plugins/{$name}/app";
    $autoload["Plugins\\{$name}\\Database\\Factories\\"] = "plugins/{$name}/database/factories";
    $autoload["Plugins\\{$name}\\Database\\Seeders\\"] = "plugins/{$name}/database/seeders";
    $autoload["Plugins\\{$name}\\Tests\\"] = "plugins/{$name}/tests";
}

foreach ($themes as $name) {
    $autoload["Themes\\{$name}\\App\\"] = "themes/{$name}/app";
    $autoload["Themes\\{$name}\\Database\\Factories\\"] = "themes/{$name}/database/factories";
    $autoload["Themes\\{$name}\\Database\\Seeders\\"] = "themes/{$name}/database/seeders";
}

// composer.local.json を生成
$composerLocal = [
    '_comment' => 'このファイルは自動生成されます。手動で編集しないでください。',
    '_generated_at' => date('Y-m-d H:i:s'),
    'autoload' => [
        'psr-4' => $autoload,
    ],
];

$json = json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents($composerLocalPath, $json."\n");

$pluginCount = count($plugins);
$themeCount = count($themes);
echo "composer.local.json synced ({$pluginCount} plugins, {$themeCount} themes)\n";
