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

use App\DTO\Plugin\EnabledPluginRecord;
use Illuminate\Support\Collection;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * プラグインリポジトリインターフェース
 *
 * 有効化されたプラグインの情報を取得するための抽象レイヤー。
 * Plugin Eloquent モデルへの直接依存を排除し、SDK分離を可能にする。
 */
interface PluginRepositoryInterface
{
    /**
     * 有効化されたプラグインの一覧を取得
     *
     * pluginsテーブルが存在しない場合は空コレクションを返す。
     *
     * @return Collection<int, EnabledPluginRecord>
     */
    public function getEnabled(): Collection;
}
