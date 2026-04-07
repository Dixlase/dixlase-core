<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Console\Commands\SyncGitExclude;
use Illuminate\Support\Facades\Artisan;

/**
 * .git/info/exclude ファイル管理ヘルパー
 *
 * このヘルパーは SyncGitExclude コマンドのラッパーです。
 * 管理画面からの呼び出しや、プログラム内での簡易利用に使用します。
 *
 * @see \App\Console\Commands\SyncGitExclude
 */
class GitExcludeHelper
{
    /**
     * プラグインの除外ルールを追加
     *
     * @param  string  $pluginName  プラグイン名（例: DixlaseMenus）
     * @return bool 成功したかどうか
     */
    public static function addPluginExclusion(string $pluginName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-git-exclude', [
                '--add-plugin' => $pluginName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to add plugin exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * プラグインの除外ルールを削除
     *
     * @param  string  $pluginName  プラグイン名
     * @return bool 成功したかどうか
     */
    public static function removePluginExclusion(string $pluginName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-git-exclude', [
                '--remove-plugin' => $pluginName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to remove plugin exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * テーマの除外ルールを追加
     *
     * @param  string  $themeName  テーマ名
     * @return bool 成功したかどうか
     */
    public static function addThemeExclusion(string $themeName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-git-exclude', [
                '--add-theme' => $themeName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to add theme exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * テーマの除外ルールを削除
     *
     * @param  string  $themeName  テーマ名
     * @return bool 成功したかどうか
     */
    public static function removeThemeExclusion(string $themeName): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-git-exclude', [
                '--remove-theme' => $themeName,
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to remove theme exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * すべてのプラグイン・テーマの除外ルールを同期
     *
     * @return bool 成功したかどうか
     */
    public static function syncAll(): bool
    {
        try {
            $exitCode = Artisan::call('dls:sync-git-exclude', [
                '--force' => true,
            ]);

            return $exitCode === 0;
        } catch (\Exception $e) {
            \Log::error('Failed to sync git exclude: '.$e->getMessage());

            return false;
        }
    }

    /**
     * プラグインの除外ルールが存在するか確認
     *
     * @param  string  $pluginName  プラグイン名
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        return SyncGitExclude::hasPluginExclusion($pluginName);
    }

    /**
     * テーマの除外ルールが存在するか確認
     *
     * @param  string  $themeName  テーマ名
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        return SyncGitExclude::hasThemeExclusion($themeName);
    }
}
