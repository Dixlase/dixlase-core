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

namespace App\Console\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * テーマ管理の共通機能を提供するトレイト
 */
trait ThemeManagementTrait
{

    /**
     * 現在の環境が本番環境かどうかを判定
     *
     * @return bool
     */
    protected function isProduction(): bool
    {
        return app()->environment('production');
    }

    /**
     * 本番環境での実行を確認する
     *
     * @param string $action 説明文（例: "migrate"）
     * @return bool
     */
    protected function confirmProductionAction(string $action): bool
    {
        if (!$this->isProduction()) {
            return true;
        }

        $this->warn(__('command.production_warning', ['action' => $action]));
        return $this->confirm(__('command.production_confirm'));
    }

    /**
     * テーマディレクトリのパスを取得
     *
     * @param string $themeName テーマ名
     * @return string
     */
    protected function getThemePath(string $themeName): string
    {
        return base_path("themes/{$themeName}");
    }

    /**
     * テーマが存在するか確認
     *
     * @param string $themeName テーマ名
     * @return bool
     */
    protected function themeExists(string $themeName): bool
    {
        $themePath = $this->getThemePath($themeName);
        return File::isDirectory($themePath) && File::exists("{$themePath}/theme.json");
    }

    /**
     * テーマ情報を取得
     *
     * @param string $themeName テーマ名
     * @return array|null
     */
    protected function getThemeInfo(string $themeName): ?array
    {
        $themePath = $this->getThemePath($themeName);
        $themeJsonPath = "{$themePath}/theme.json";

        if (!File::exists($themeJsonPath)) {
            return null;
        }

        $content = File::get($themeJsonPath);
        return json_decode($content, true);
    }

    /**
     * テーマの名前空間を取得
     *
     * @param string $themeName テーマ名
     * @return string
     */
    protected function getThemeNamespace(string $themeName): string
    {
        $themeInfo = $this->getThemeInfo($themeName);
        
        if ($themeInfo && isset($themeInfo['namespace'])) {
            return $themeInfo['namespace'];
        }

        // デフォルトの名前空間を生成
        return "Themes\\{$themeName}\\App";
    }

    /**
     * テーマのテーブルプレフィックスを取得
     *
     * @param string $themeName テーマ名
     * @return string
     */
    protected function getThemeTablePrefix(string $themeName): string
    {
        $themeInfo = $this->getThemeInfo($themeName);
        
        if ($themeInfo && isset($themeInfo['table_prefix'])) {
            return $themeInfo['table_prefix'];
        }

        // デフォルトのプレフィックスを生成（例: thm_my_theme_）
        $slug = $themeInfo['slug'] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $themeName));
        return 'thm_' . str_replace('-', '_', $slug) . '_';
    }

    /**
     * 利用可能なテーマのリストを取得
     *
     * @return array
     */
    protected function getAvailableThemes(): array
    {
        $themesPath = base_path('themes');
        
        if (!File::isDirectory($themesPath)) {
            return [];
        }

        $themes = [];
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $themeName = basename($directory);
            if ($this->themeExists($themeName)) {
                $themes[] = $themeName;
            }
        }

        return $themes;
    }

    /**
     * テーマのディレクトリ構造を作成
     *
     * @param string $themeName テーマ名
     * @param array $directories 作成するディレクトリのリスト
     * @return void
     */
    protected function createThemeDirectories(string $themeName, array $directories): void
    {
        $themePath = $this->getThemePath($themeName);

        foreach ($directories as $directory) {
            $fullPath = "{$themePath}/{$directory}";
            if (!File::isDirectory($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }
        }
    }

    /**
     * テーマファイルを削除
     *
     * @param string $themeName テーマ名
     * @param string $relativePath テーマディレクトリからの相対パス
     * @return bool
     */
    protected function deleteThemeFile(string $themeName, string $relativePath): bool
    {
        $themePath = $this->getThemePath($themeName);
        $filePath = "{$themePath}/{$relativePath}";

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    /**
     * テーマディレクトリを削除
     *
     * @param string $themeName テーマ名
     * @param string $relativePath テーマディレクトリからの相対パス
     * @return bool
     */
    protected function deleteThemeDirectory(string $themeName, string $relativePath): bool
    {
        $themePath = $this->getThemePath($themeName);
        $dirPath = "{$themePath}/{$relativePath}";

        if (File::isDirectory($dirPath)) {
            return File::deleteDirectory($dirPath);
        }

        return false;
    }
}
