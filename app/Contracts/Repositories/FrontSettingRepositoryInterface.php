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

namespace App\Contracts\Repositories;

use App\Models\FrontSetting;

/**
 * フロント設定リポジトリインターフェース
 *
 * フロントページの設定（OGP画像、説明文など）を管理します。
 */
interface FrontSettingRepositoryInterface extends SettingRepositoryInterface
{
    /**
     * リレーションを含めて設定を取得
     *
     * @param  string  $name  設定名
     */
    public function findWithRelations(string $name): ?FrontSetting;
}
