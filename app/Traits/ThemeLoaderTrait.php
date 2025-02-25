<?php

/**
 * This file is part of MySoftware.
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


namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Theme;
use Illuminate\Support\Facades\Schema;

trait ThemeLoaderTrait
{
    /**
     * 有効化されているテーマを取得する
     *
     * @return int
     */
    public function getActiveTheme(): int
    {
        //themes_settingsテーブルのactive_theme_idの値を取得
        //themes_settingsテーブルが存在しているか確認
        if (Schema::hasTable('themes_settings')) {
            $activeTheme = DB::table('themes_settings')->first();
            $activeThemeId = $activeTheme->active_theme_id;
        } else {
            $activeThemeId = 1;
        }
        return $activeThemeId;
    }

    /**
     * 現在アクティブなテーマのディレクトリ名を取得
     *
     * @return string
     */
    public function getActiveThemeDirectory(): string
    {

        $activeThemeId = $this->getActiveTheme();
        if (Schema::hasTable('themes')) {
            $theme = Theme::find($activeThemeId);
        } else {
            $theme = config('themes.default_theme', env('APP_THEME', 'DefaultTheme'));
        }

        return $theme;
    }

    /**
     * テーマアセットの完全URLを生成
     *
     * @param string $path
     * @return string
     */
    public function themeAsset(string $path): string
    {
        $themeDirectory = $this->getActiveThemeDirectory();
        return asset("themes/{$themeDirectory}/{$path}");
    }
}
