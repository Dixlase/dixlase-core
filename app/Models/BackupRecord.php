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

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * バックアップ記録モデル
 *
 * バックアッププラグインが作成したバックアップのメタデータを管理します。
 */
class BackupRecord extends Model
{
    // ========================================
    // ステータス定数
    // ========================================

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DELETED = 'deleted';

    // ========================================
    // 検証ステータス定数
    // ========================================

    public const VERIFICATION_UNCHECKED = 'unchecked';

    public const VERIFICATION_VALID = 'valid';

    public const VERIFICATION_INVALID = 'invalid';

    // ========================================
    // タイプ定数
    // ========================================

    public const TYPE_FILES = 'files';

    public const TYPE_DATABASE = 'database';

    public const TYPE_FULL = 'full';

    protected $table = 'backup_records';

    protected $fillable = [
        'site_id',
        'plugin_slug',
        'type',
        'targets',
        'file_path',
        'file_name',
        'file_size',
        'is_encrypted',
        'encryption_algorithm',
        'hash',
        'hash_algorithm',
        'verification_status',
        'last_verified_at',
        'retention_until',
        'metadata',
        'status',
    ];

    protected $casts = [
        'targets' => 'array',
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
        'metadata' => 'array',
        'last_verified_at' => 'datetime',
        'retention_until' => 'datetime',
    ];

    // ========================================
    // スコープ
    // ========================================

    /**
     * 指定プラグインのバックアップに絞り込み
     */
    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    /**
     * 暗号化されたバックアップに絞り込み
     */
    public function scopeEncrypted(Builder $query): Builder
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * 検証済み（valid）のバックアップに絞り込み
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('verification_status', self::VERIFICATION_VALID);
    }

    /**
     * リテンション期限切れのバックアップに絞り込み
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('retention_until', '<=', now());
    }

    /**
     * バックアップ種別で絞り込み
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ========================================
    // ステータス変更メソッド
    // ========================================

    /**
     * 検証済みとしてマーク
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'verification_status' => self::VERIFICATION_VALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * 検証失敗としてマーク
     */
    public function markAsInvalid(): bool
    {
        return $this->update([
            'verification_status' => self::VERIFICATION_INVALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * 期限切れとしてマーク
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * 削除済みとしてマーク
     */
    public function markAsDeleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELETED,
        ]);
    }

    // ========================================
    // ヘルパーメソッド
    // ========================================

    /**
     * リテンション期限切れかどうかを判定
     */
    public function isExpired(): bool
    {
        if ($this->retention_until === null) {
            return false;
        }

        return $this->retention_until->isPast();
    }

    /**
     * メタデータから指定キーの値を取得
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->metadata ?? [];

        return $metadata[$key] ?? $default;
    }
}
