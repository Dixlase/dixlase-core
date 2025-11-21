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

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Theme;
use Illuminate\Support\Facades\Schema;

trait ThemeLoaderTrait
{
    /**
     * 有効なテーマIDを取得
     *
     * @return int
     */
    public function getEnabledTheme(): int
    {
        //theme_settingsテーブルのenabled_theme_idの値を取得
        //theme_settingsテーブルが存在しているか確認
        if (Schema::hasTable('theme_settings')) {
            $themeSetting = DB::table('theme_settings')->first();
            $enabledThemeId = $themeSetting ? $themeSetting->enabled_theme_id : 1;
        } else {
            $enabledThemeId = 1;
        }
        return $enabledThemeId;
    }

    /**
     * 現在有効なテーマのディレクトリ名を取得
     *
     * @return string
     */
    public function getEnabledThemeDirectory(): string
    {
        $enabledThemeId = $this->getEnabledTheme();
        
        if (Schema::hasTable('themes')) {
            $theme = Theme::find($enabledThemeId);
            if ($theme) {
                return $theme->directory ?? config('themes.default_theme', env('APP_THEME', 'DixlaseDefaultTheme'));
            }
        }
        
        return config('themes.default_theme', env('APP_THEME', 'DixlaseDefaultTheme'));
    }

    /**
     * テーマアセットの完全URLを生成
     *
     * @param string $path
     * @return string
     */
    public function themeAsset(string $path): string
    {
        $themeDirectory = $this->getEnabledThemeDirectory();
        return asset("themes/{$themeDirectory}/{$path}");
    }
}
