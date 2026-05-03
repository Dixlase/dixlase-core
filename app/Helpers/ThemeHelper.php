<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class ThemeHelper
{
    /**
     * 有効化されているテーマを取得
     */
    public static function getActiveTheme(): ?Theme
    {
        try {
            if (! Schema::hasTable('themes') || ! Schema::hasTable('theme_settings')) {
                return null;
            }

            // theme_settingsテーブルから有効化されているテーマIDを取得
            $themeSetting = \DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->first();

            if (! $themeSetting || ! $themeSetting->value) {
                return null;
            }

            return Theme::find((int) $themeSetting->value);
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to get active theme', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 有効化されているテーマのディレクトリパスを取得
     */
    public static function getActiveThemePath(): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        return base_path("themes/{$theme->directory}");
    }

    /**
     * 有効化されているテーマの管理画面ルートを読み込む
     *
     * このメソッドはroutes/admin.php内の認証済みルートグループ内で呼び出される
     * ことを想定しています。
     */
    public static function loadEnabledThemeAdminRoutes(): void
    {
        try {
            $activeTheme = self::getActiveTheme();

            if (! $activeTheme) {
                return;
            }

            $adminRoutePath = base_path("themes/{$activeTheme->directory}/routes/admin.php");

            if (File::exists($adminRoutePath)) {
                include $adminRoutePath;
            }
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to load theme admin routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 有効化されているテーマのWebルートを読み込む
     */
    public static function loadEnabledThemeWebRoutes(): void
    {
        try {
            $activeTheme = self::getActiveTheme();

            if (! $activeTheme) {
                return;
            }

            $webRoutePath = base_path("themes/{$activeTheme->directory}/routes/web.php");

            if (File::exists($webRoutePath)) {
                include $webRoutePath;
            }
        } catch (\Exception $e) {
            Log::error('ThemeHelper: Failed to load theme web routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * テーマのアセットパスを取得
     */
    public static function asset(string $path = ''): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        $basePath = "themes/{$theme->directory}/public";

        return $path ? "{$basePath}/{$path}" : $basePath;
    }

    /**
     * テーマのビューパスを取得
     */
    public static function view(string $view): ?string
    {
        $theme = self::getActiveTheme();

        if (! $theme) {
            return null;
        }

        return "themes::{$view}";
    }
}
