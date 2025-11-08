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
     * プラグイン用動的除外設定の開始マーカー
     */
    private const PLUGIN_SECTION_START = '# === プラグイン用除外設定（自動管理） ===';

    /**
     * プラグイン用動的除外設定の終了マーカー
     */
    private const PLUGIN_SECTION_END = '# === プラグイン用除外設定終了 ===';

    /**
     * 初期化
     */
    private static function init(): void
    {
        self::$gitignorePath = base_path('.gitignore');
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
            // プラグインリストを取得
            $plugins = self::getPluginList();

            // 既に存在する場合はスキップ
            if (in_array($pluginName, $plugins)) {
                return true;
            }

            // プラグインを追加
            $plugins[] = $pluginName;
            sort($plugins); // アルファベット順にソート

            // .gitignoreファイルを更新
            self::updateGitignore($plugins);

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
            // プラグインリストを取得
            $plugins = self::getPluginList();

            // プラグインを削除
            $plugins = array_filter($plugins, fn($plugin) => $plugin !== $pluginName);
            $plugins = array_values($plugins); // インデックスを再構築

            // .gitignoreファイルを更新
            self::updateGitignore($plugins);

            Log::info("Plugin '{$pluginName}' removed from .gitignore exclusions");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to remove plugin '{$pluginName}' from .gitignore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * .gitignoreファイルのプラグインセクションを更新
     *
     * @param array $plugins プラグインリスト
     */
    private static function updateGitignore(array $plugins): void
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
            $pluginSection = self::generatePluginSection($plugins);
            array_splice($lines, $startIndex, 0, $pluginSection);

            // ファイルに書き戻し
            File::put(self::$gitignorePath, implode("\n", $lines));
        } else {
            // プラグインセクションが存在しない場合は追加
            $pluginSection = self::generatePluginSection($plugins);
            $lines = array_merge($lines, [''], $pluginSection);
            File::put(self::$gitignorePath, implode("\n", $lines));
        }
    }

    /**
     * プラグインセクションを生成
     *
     * @param array $plugins プラグインリスト
     * @return array
     */
    private static function generatePluginSection(array $plugins): array
    {
        $section = [];

        $section[] = self::PLUGIN_SECTION_START;
        
        if (empty($plugins)) {
            $section[] = '# プラグインが追加されると、ここに自動的に除外設定が追加されます';
        } else {
            foreach ($plugins as $plugin) {
                $section[] = "!plugins/{$plugin}";
            }
        }

        $section[] = self::PLUGIN_SECTION_END;

        return $section;
    }

    /**
     * .gitignoreファイルからプラグインリストを取得
     *
     * @return array
     */
    private static function getPluginList(): array
    {
        if (!File::exists(self::$gitignorePath)) {
            return [];
        }

        $content = File::get(self::$gitignorePath);
        $lines = explode("\n", $content);
        $plugins = [];
        $inPluginSection = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // プラグインセクションの開始
            if ($trimmed === self::PLUGIN_SECTION_START) {
                $inPluginSection = true;
                continue;
            }
            
            // プラグインセクションの終了
            if ($trimmed === self::PLUGIN_SECTION_END) {
                break;
            }
            
            // プラグインセクション内のプラグイン行を抽出
            if ($inPluginSection && preg_match('/^!plugins\/([^\/\s]+)/', $trimmed, $matches)) {
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
