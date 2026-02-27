<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Contracts\Repositories;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * テーマリポジトリインターフェース
 *
 * 有効化されたテーマの情報を取得するための抽象レイヤー。
 * Theme Eloquent モデルや DB ファサードへの直接依存を排除し、SDK分離を可能にする。
 */
interface ThemeRepositoryInterface
{
    /**
     * 有効なテーマIDを取得
     *
     * theme_settingsテーブルが存在しない場合はデフォルト値 1 を返す。
     */
    public function getEnabledThemeId(): int;

    /**
     * 有効なテーマのディレクトリ名を取得
     *
     * テーマが見つからない場合は config('themes.default_theme') にフォールバックする。
     */
    public function getEnabledThemeDirectory(): string;
}
