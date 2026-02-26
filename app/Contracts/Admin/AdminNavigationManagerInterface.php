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

namespace App\Contracts\Admin;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 管理画面ナビゲーション管理インターフェース
 *
 * プラグインのナビゲーション設定をコアのナビゲーションにマージするための抽象レイヤー。
 * AdminHelper への直接依存を排除し、SDK分離を可能にする。
 */
interface AdminNavigationManagerInterface
{
    /**
     * 新構造のナビゲーションファイル (config/admin/navigation.php) をマージ
     *
     * _insert_before / _insert_after による順序制御をサポートする。
     * ファイルが存在しない場合は何もしない。
     *
     * @param  string  $configFile  プラグインのナビゲーション設定ファイルパス
     */
    public function mergeNavigationFile(string $configFile): void;

    /**
     * 旧構造のナビゲーション設定 (admin.php の nav キー) をマージ
     *
     * _insert_before / _insert_after による順序制御をサポートする。
     * ファイルが存在しない場合、または nav キーがない場合は何もしない。
     *
     * @param  string  $configFile  プラグインの admin 設定ファイルパス
     */
    public function mergeNavConfig(string $configFile): void;
}
