<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
 * .gitignore ファイル管理ヘルパー
 * 
 * プラグインやテーマの除外ルールを .gitignore に追加・削除する
 */
class GitIgnoreHelper
{
    /**
     * .gitignoreファイルのパス
     */
    protected static function getGitIgnorePath(): string
    {
        return base_path(".gitignore");
    }

    /**
     * プラグインの除外ルール（!plugins/PluginName/）を追加
     *
     * @param string $pluginName プラグイン名（例: DixlaseBackup）
     * @return bool 成功したかどうか
     */
    public static function addPluginExclusion(string $pluginName): bool
    {
        return self::addExclusion("plugins", $pluginName);
    }

    /**
     * プラグインの除外ルールを削除
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功したかどうか
     */
    public static function removePluginExclusion(string $pluginName): bool
    {
        return self::removeExclusion("plugins", $pluginName);
    }

    /**
     * テーマの除外ルール（!themes/ThemeName/）を追加
     *
     * @param string $themeName テーマ名
     * @return bool 成功したかどうか
     */
    public static function addThemeExclusion(string $themeName): bool
    {
        return self::addExclusion("themes", $themeName);
    }

    /**
     * テーマの除外ルールを削除
     *
     * @param string $themeName テーマ名
     * @return bool 成功したかどうか
     */
    public static function removeThemeExclusion(string $themeName): bool
    {
        return self::removeExclusion("themes", $themeName);
    }

    /**
     * 除外ルールを追加
     *
     * @param string $type "plugins" または "themes"
     * @param string $name ディレクトリ名
     * @return bool
     */
    protected static function addExclusion(string $type, string $name): bool
    {
        try {
            $gitIgnorePath = self::getGitIgnorePath();

            if (!File::exists($gitIgnorePath)) {
                Log::warning(".gitignore file not found");
                return false;
            }

            $content = File::get($gitIgnorePath);
            $exclusionLine = "!{$type}/{$name}/";

            // 既に追加されている場合はスキップ
            if (str_contains($content, $exclusionLine)) {
                Log::info("{$type} exclusion already exists in .gitignore: {$exclusionLine}");
                return true;
            }

            // 挿入位置を探す
            // パターン: "{type}/*" の後、"themes/*" の前（pluginsの場合）
            $lines = explode("\n", $content);
            $insertIndex = self::findInsertIndex($lines, $type, $name);

            if ($insertIndex === -1) {
                // 適切な位置が見つからない場合は末尾に追加
                $content = rtrim($content) . "\n{$exclusionLine}\n";
            } else {
                array_splice($lines, $insertIndex, 0, [$exclusionLine]);
                $content = implode("\n", $lines);
            }

            File::put($gitIgnorePath, $content);
            Log::info("Added {$type} exclusion to .gitignore: {$exclusionLine}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add {$type} exclusion to .gitignore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 除外ルールを削除
     *
     * @param string $type "plugins" または "themes"
     * @param string $name ディレクトリ名
     * @return bool
     */
    protected static function removeExclusion(string $type, string $name): bool
    {
        try {
            $gitIgnorePath = self::getGitIgnorePath();

            if (!File::exists($gitIgnorePath)) {
                return true; // ファイルが存在しない場合は成功とみなす
            }

            $content = File::get($gitIgnorePath);
            $exclusionLine = "!{$type}/{$name}/";

            // 該当行を削除
            $lines = explode("\n", $content);
            $filteredLines = array_filter($lines, function ($line) use ($exclusionLine) {
                return trim($line) !== $exclusionLine;
            });

            $content = implode("\n", array_values($filteredLines));
            File::put($gitIgnorePath, $content);

            Log::info("Removed {$type} exclusion from .gitignore: {$exclusionLine}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove {$type} exclusion from .gitignore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 挿入位置を探す
     * 
     * .gitignoreの構造:
     * plugins/*
     * !plugins/ExistingPlugin/
     * themes/*
     * !themes/ExistingTheme/
     *
     * @param array $lines .gitignoreの行配列
     * @param string $type "plugins" または "themes"
     * @param string $name 追加するディレクトリ名
     * @return int 挿入位置（-1の場合は末尾）
     */
    protected static function findInsertIndex(array $lines, string $type, string $name): int
    {
        $basePattern = "{$type}/*";
        $nextSectionPattern = ($type === "plugins") ? "themes/*" : null;
        
        $baseIndex = -1;
        $lastExclusionIndex = -1;
        $nextSectionIndex = -1;

        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            // ベースパターン（plugins/* または themes/*）を探す
            if ($trimmedLine === $basePattern) {
                $baseIndex = $index;
            }

            // 既存の除外ルールを探す
            if (str_starts_with($trimmedLine, "!{$type}/")) {
                $lastExclusionIndex = $index;
            }

            // 次のセクション（themes/*）を探す（pluginsの場合のみ）
            if ($nextSectionPattern && $trimmedLine === $nextSectionPattern) {
                $nextSectionIndex = $index;
                break; // これ以降は探さない
            }
        }

        // 挿入位置を決定
        if ($lastExclusionIndex !== -1) {
            // 既存の除外ルールの後に挿入（アルファベット順にソート）
            return self::findSortedInsertIndex($lines, $type, $name, $baseIndex, $nextSectionIndex);
        } elseif ($baseIndex !== -1) {
            // ベースパターンの直後に挿入
            return $baseIndex + 1;
        }

        return -1;
    }

    /**
     * アルファベット順でソートされた挿入位置を探す
     *
     * @param array $lines
     * @param string $type
     * @param string $name
     * @param int $startIndex
     * @param int $endIndex
     * @return int
     */
    protected static function findSortedInsertIndex(array $lines, string $type, string $name, int $startIndex, int $endIndex): int
    {
        $endIndex = ($endIndex === -1) ? count($lines) : $endIndex;
        $newExclusion = "!{$type}/{$name}/";

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            $line = trim($lines[$i]);

            // 除外ルールでない行に到達したら、その位置に挿入
            if (!str_starts_with($line, "!{$type}/")) {
                return $i;
            }

            // アルファベット順で比較
            if (strcasecmp($line, $newExclusion) > 0) {
                return $i;
            }
        }

        return $endIndex;
    }

    /**
     * プラグインの除外ルールが存在するか確認
     *
     * @param string $pluginName プラグイン名
     * @return bool
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        return self::hasExclusion("plugins", $pluginName);
    }

    /**
     * テーマの除外ルールが存在するか確認
     *
     * @param string $themeName テーマ名
     * @return bool
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        return self::hasExclusion("themes", $themeName);
    }

    /**
     * 除外ルールが存在するか確認
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    protected static function hasExclusion(string $type, string $name): bool
    {
        $gitIgnorePath = self::getGitIgnorePath();

        if (!File::exists($gitIgnorePath)) {
            return false;
        }

        $content = File::get($gitIgnorePath);
        return str_contains($content, "!{$type}/{$name}/");
    }

    /**
     * .gitignoreの内容を取得
     *
     * @return string|null
     */
    public static function getContent(): ?string
    {
        $gitIgnorePath = self::getGitIgnorePath();

        if (!File::exists($gitIgnorePath)) {
            return null;
        }

        return File::get($gitIgnorePath);
    }
}
