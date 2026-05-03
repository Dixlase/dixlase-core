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

namespace App\Repositories;

use App\Contracts\Repositories\ThemeRepositoryInterface;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * テーマリポジトリ実装
 *
 * Theme Eloquent モデルと theme_settings テーブルを使用して有効テーマ情報を取得する。
 * テーブル存在チェックを内包し、マイグレーション未実行時にも安全に動作する。
 */
class ThemeRepository implements ThemeRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getEnabledThemeId(): int
    {
        if (! Schema::hasTable('theme_settings')) {
            return 1;
        }

        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();

        return $themeSetting ? (int) $themeSetting->value : 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabledThemeDirectory(): string
    {
        $enabledThemeId = $this->getEnabledThemeId();

        if (Schema::hasTable('themes')) {
            $theme = Theme::find($enabledThemeId);
            if ($theme) {
                return $theme->directory ?? $this->getDefaultThemeDirectory();
            }
        }

        return $this->getDefaultThemeDirectory();
    }

    /**
     * デフォルトテーマのディレクトリ名を取得
     */
    private function getDefaultThemeDirectory(): string
    {
        return config('themes.default_theme', env('APP_THEME', 'DixlaseOnePage'));
    }
}
