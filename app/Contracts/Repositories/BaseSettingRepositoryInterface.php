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

namespace App\Contracts\Repositories;

use App\Models\BaseSetting;

/**
 * 基本設定リポジトリインターフェース
 * 
 * サイトの基本設定（サイト名、OGP画像など）を管理します。
 */
interface BaseSettingRepositoryInterface extends SettingRepositoryInterface
{
    /**
     * リレーションを含めて設定を取得
     *
     * @param string $name 設定名
     * @return BaseSetting|null
     */
    public function findWithRelations(string $name): ?BaseSetting;
}
