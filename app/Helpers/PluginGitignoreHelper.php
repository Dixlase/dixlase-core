<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

class PluginGitignoreHelper
{
    /**
     * .gitignoreファイルのパス
     */
    private static string $gitignorePath;

    /**
     * プラグイン設定ファイルのパス
     */
    private static string $pluginConfigPath;

    /**
     * プラグイン用動的除外設定の開始マーカー
     */
    private const PLUGIN_SECTION_START = '# === プラグイン用動的除外設定 (自動管理) ===';

    /**
     * プラグイン用動的除外設定の終了マーカー
     */
    private const PLUGIN_SECTION_END = '# === プラグイン用動的除外設定終了 ===';

    /**
     * 初期化
     */
    private static function init(): void
    {
        self::$gitignorePath = base_path('.gitignore');
        self::$pluginConfigPath = base_path('.gitignore.plugins');
    }

    /**
     * プラグインを.gitignore除外リストに追加
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功した場合true
     */
    public static function addPlugin(string $pluginName): bool
    {
        self::init();

        try {
            // .gitignore.pluginsファイルを更新
            self::updatePluginConfig($pluginName, 'add');

            // .gitignoreファイルを更新
            self::updateGitignore();

            Log::info("Plugin '{$pluginName}' added to .gitignore exclusions");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to add plugin '{$pluginName}' to .gitignore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * プラグインを.gitignore除外リストから削除
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功した場合true
     */
    public static function removePlugin(string $pluginName): bool
    {
        self::init();

        try {
            // .gitignore.pluginsファイルを更新
            self::updatePluginConfig($pluginName, 'remove');

            // .gitignoreファイルを更新
            self::updateGitignore();

            Log::info("Plugin '{$pluginName}' removed from .gitignore exclusions");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to remove plugin '{$pluginName}' from .gitignore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * .gitignore.pluginsファイルを更新
     *
     * @param string $pluginName プラグイン名
     * @param string $action 'add' または 'remove'
     */
    private static function updatePluginConfig(string $pluginName, string $action): void
    {
        $plugins = self::getPluginList();

        if ($action === 'add') {
            if (!in_array($pluginName, $plugins)) {
                $plugins[] = $pluginName;
            }
        } elseif ($action === 'remove') {
            $plugins = array_filter($plugins, fn($plugin) => $plugin !== $pluginName);
        }

        // .gitignore.pluginsファイルに書き込み
        $content = "# プラグイン用動的除外設定\n";
        $content .= "# このファイルはプラグインのインストール/アンインストール時に自動更新されます\n\n";
        $content .= "# 開発中のプラグイン（除外解除）\n";

        foreach ($plugins as $plugin) {
            $content .= "!plugins/{$plugin}/\n";
        }

        File::put(self::$pluginConfigPath, $content);
    }

    /**
     * .gitignoreファイルのプラグインセクションを更新
     */
    private static function updateGitignore(): void
    {
        if (!File::exists(self::$gitignorePath)) {
            return;
        }

        $content = File::get(self::$gitignorePath);
        $lines = explode("\n", $content);

        $startIndex = null;
        $endIndex = null;

        // プラグインセクションの開始・終了位置を検索
        foreach ($lines as $index => $line) {
            if (trim($line) === self::PLUGIN_SECTION_START) {
                $startIndex = $index;
            } elseif (trim($line) === self::PLUGIN_SECTION_END) {
                $endIndex = $index;
                break;
            }
        }

        if ($startIndex !== null && $endIndex !== null) {
            // 既存のプラグインセクションを削除
            array_splice($lines, $startIndex, $endIndex - $startIndex + 1);

            // 新しいプラグインセクションを挿入
            $pluginSection = self::generatePluginSection();
            array_splice($lines, $startIndex, 0, $pluginSection);

            // ファイルに書き戻し
            File::put(self::$gitignorePath, implode("\n", $lines));
        }
    }

    /**
     * プラグインセクションを生成
     *
     * @return array
     */
    private static function generatePluginSection(): array
    {
        $plugins = self::getPluginList();
        $section = [];

        $section[] = self::PLUGIN_SECTION_START;
        $section[] = '# 以下の行はプラグインシステムによって自動管理されます';

        foreach ($plugins as $plugin) {
            $section[] = "!plugins/{$plugin}/";
        }

        $section[] = self::PLUGIN_SECTION_END;

        return $section;
    }

    /**
     * .gitignore.pluginsファイルからプラグインリストを取得
     *
     * @return array
     */
    private static function getPluginList(): array
    {
        if (!File::exists(self::$pluginConfigPath)) {
            return [];
        }

        $content = File::get(self::$pluginConfigPath);
        $lines = explode("\n", $content);
        $plugins = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^!plugins\/([^\/]+)\/$/', $line, $matches)) {
                $plugins[] = $matches[1];
            }
        }

        return array_unique($plugins);
    }

    /**
     * 現在の除外プラグインリストを取得
     *
     * @return array
     */
    public static function getExcludedPlugins(): array
    {
        self::init();
        return self::getPluginList();
    }
}
