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
 */

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 */
class ComposerLocalHelper
{
    /**
     * composer.local.jsonファイルのパス
     */
    protected static function getComposerLocalPath(): string
    {
        return base_path('composer.local.json');
    }

    /**
     * plugins/とthemes/ディレクトリから自動的にcomposer.local.jsonを生成
     *
     * @return bool 成功したかどうか
     */
    public static function syncAutoload(): bool
    {
        try {
            $composerLocalPath = self::getComposerLocalPath();

            // プラグインディレクトリを検出
            $plugins = self::detectPluginDirectories();

            // テーマディレクトリを検出
            $themes = self::detectThemeDirectories();

            // autoload設定を生成
            $autoload = [];

            // プラグインのautoload設定
            foreach ($plugins as $pluginName) {
                $autoload["Plugins\\{$pluginName}\\App\\"] = "plugins/{$pluginName}/app";
                $autoload["Plugins\\{$pluginName}\\Database\\Factories\\"] = "plugins/{$pluginName}/database/factories";
                $autoload["Plugins\\{$pluginName}\\Database\\Seeders\\"] = "plugins/{$pluginName}/database/seeders";
            }

            // テーマのautoload設定
            foreach ($themes as $themeName) {
                $autoload["Themes\\{$themeName}\\App\\"] = "themes/{$themeName}/app";
                $autoload["Themes\\{$themeName}\\Database\\Factories\\"] = "themes/{$themeName}/database/factories";
                $autoload["Themes\\{$themeName}\\Database\\Seeders\\"] = "themes/{$themeName}/database/seeders";
            }

            // composer.local.jsonの内容を生成
            $composerLocal = [
                '_comment' => 'このファイルは自動生成されます。手動で編集しないでください。',
                '_generated_at' => date('Y-m-d H:i:s'),
                'autoload' => [
                    'psr-4' => $autoload,
                ],
            ];

            // JSONファイルとして保存
            $json = json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            file_put_contents($composerLocalPath, json_encode($composerLocal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync composer.local.json: '.$e->getMessage());

            return false;
        }
    }

    /**
     * plugins/ディレクトリ内のプラグインディレクトリを検出
     *
     * @return array プラグインディレクトリ名の配列
     */
    protected static function detectPluginDirectories(): array
    {
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return [];
        }

        $directories = File::directories($pluginsPath);
        $pluginNames = [];

        foreach ($directories as $directory) {
            $pluginName = basename($directory);

            // .で始まるディレクトリは除外
            if (str_starts_with($pluginName, '.')) {
                continue;
            }

            // app/ディレクトリが存在するか確認
            if (File::exists($directory.'/app')) {
                $pluginNames[] = $pluginName;
            }
        }

        return $pluginNames;
    }

    /**
     * themes/ディレクトリ内のテーマディレクトリを検出
     *
     * @return array テーマディレクトリ名の配列
     */
    protected static function detectThemeDirectories(): array
    {
        $themesPath = base_path('themes');

        if (! File::exists($themesPath)) {
            return [];
        }

        $directories = File::directories($themesPath);
        $themeNames = [];

        foreach ($directories as $directory) {
            $themeName = basename($directory);

            // .で始まるディレクトリは除外
            if (str_starts_with($themeName, '.')) {
                continue;
            }

            // app/ディレクトリが存在するか確認
            if (File::exists($directory.'/app')) {
                $themeNames[] = $themeName;
            }
        }

        return $themeNames;
    }

    /**
     * composer.local.jsonの内容を取得
     */
    public static function getComposerLocalContent(): ?array
    {
        $composerLocalPath = self::getComposerLocalPath();

        if (! File::exists($composerLocalPath)) {
            return null;
        }

        $content = File::get($composerLocalPath);

        return json_decode($content, true);
    }

    /**
     * composer.local.jsonが存在するか確認
     */
    public static function exists(): bool
    {
        return File::exists(self::getComposerLocalPath());
    }

    /**
     * 特定のプラグインがcomposer.local.jsonに含まれているか確認
     *
     * @param  string  $pluginName  プラグイン名
     */
    public static function hasPlugin(string $pluginName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Plugins\\{$pluginName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }

    /**
     * 特定のテーマがcomposer.local.jsonに含まれているか確認
     *
     * @param  string  $themeName  テーマ名
     */
    public static function hasTheme(string $themeName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Themes\\{$themeName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }
}
