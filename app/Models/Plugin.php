<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * プラグインメタデータモデル
 */
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
        'url',
        'license',
        'description',
        'installed_at',
        'enabled_at',
        'source_id',
        'source_repo',
        'available_version',
        'last_version_check',
        'last_notified_version',
        'signing_key_id',
        'author_id',
        'authority_key_id',
        'installed_from_url',
        'installation_method',
    ];

    /**
     * キャスト設定
     */
    protected $casts = [
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'last_version_check' => 'datetime',
    ];

    /**
     * Extension source that this plugin was installed from
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Check if an update is available from the source
     */
    public function hasUpdateAvailable(): bool
    {
        return $this->available_version !== null
            && version_compare($this->available_version, $this->version, '>');
    }

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
        return ! is_null($this->installed_at);
    }

    /**
     * プラグインが有効化されているかチェック
     */
    public function isEnabled(): bool
    {
        return ! is_null($this->enabled_at);
    }

    /**
     * 後方互換性のため残す（非推奨）
     *
     * @deprecated Use isEnabled() instead
     */
    public function isActivated(): bool
    {
        return $this->isEnabled();
    }
}
