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

namespace App\Repositories;

use App\Contracts\Repositories\PluginRepositoryInterface;
use App\DTO\Plugin\EnabledPluginRecord;
use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * プラグインリポジトリ実装
 *
 * Plugin Eloquent モデルを使用して有効化されたプラグイン情報を取得する。
 * テーブル存在チェックを内包し、マイグレーション未実行時にも安全に動作する。
 */
class PluginRepository implements PluginRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getEnabled(): Collection
    {
        if (! Schema::hasTable('plugins')) {
            return collect();
        }

        return Plugin::whereNotNull('enabled_at')
            ->get()
            ->map(fn (Plugin $plugin) => new EnabledPluginRecord(
                name: $plugin->name,
                directory: $plugin->directory,
                slug: $plugin->slug,
            ));
    }
}
