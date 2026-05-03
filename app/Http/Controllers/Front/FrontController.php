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

namespace App\Http\Controllers\Front;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class FrontController extends Controller
{
    //

    // 変数を宣言する
    protected $siteName;

    protected $appearance = 'light';

    protected $viewParams = [];
    // protected $currentTheme = 'default';

    public function __construct()
    {
        // テーマ設定を取得してビューに渡す
        $this->loadThemeSettings();
    }

    /**
     * テーマ設定を読み込む
     */
    protected function loadThemeSettings(): void
    {
        try {
            // アクティブなテーマを取得
            $activeThemeId = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if (! $activeThemeId) {
                $this->viewParams['themeSettings'] = (object) [];

                return;
            }

            // テーマ情報を取得
            $theme = DB::table('themes')->find($activeThemeId);
            if (! $theme) {
                $this->viewParams['themeSettings'] = (object) [];

                return;
            }

            // テーマ固有の設定テーブル名を生成
            $settingsTableName = 'thm_'.strtolower(str_replace('-', '_', $theme->slug)).'_settings';

            // テーマ設定を取得（カラム名は'name'と'value'）
            $settings = DB::table($settingsTableName)
                ->get()
                ->pluck('value', 'name');

            // オブジェクトに変換してビューに渡す
            $this->viewParams['themeSettings'] = (object) $settings->toArray();
        } catch (\Exception $e) {
            // エラーが発生した場合は空のオブジェクトを渡す
            \Log::error('Failed to load theme settings: '.$e->getMessage());
            $this->viewParams['themeSettings'] = (object) [];
        }
    }
}
