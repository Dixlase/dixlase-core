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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    /**
     * 複数代入の許可フィールド
     */
    protected $fillable = [
        'name',
        'package_name',
        'directory',
        'namespace',
        'slug',
        'version',
        'author',
        'email',
        'web',
        'license',
        'description',
        'installed_at',
        'enabled_at',
    ];

    /**
     * キャスト設定
     */
    protected $casts = [
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];

    /**
     * 有効化されたプラグインを取得するスコープ
     */
    public function scopeEnabled($query)
    {
        return $query->whereNotNull('enabled_at');
    }

    /**
     * インストール済みプラグインのスコープ
     */
    public function scopeInstalled($query)
    {
        return $query->whereNotNull('installed_at');
    }

    /**
     * プラグインがインストール済みかチェック
     */
    public function isInstalled(): bool
    {
        return !is_null($this->installed_at);
    }

    /**
     * プラグインが有効化されているかチェック
     */
    public function isEnabled(): bool
    {
        return !is_null($this->enabled_at);
    }

    /**
     * 後方互換性のため残す（非推奨）
     * @deprecated Use isEnabled() instead
     */
    public function isActivated(): bool
    {
        return $this->isEnabled();
    }
}
