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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 復元履歴モデル
 *
 * バックアップからの復元操作の監査証跡を管理します。
 * 「誰が・いつ・何を・どのバックアップから復元したか」を記録します。
 */
class RestoreRecord extends Model
{
    // ========================================
    // ステータス定数
    // ========================================

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $table = 'restore_records';

    protected $fillable = [
        'site_id',
        'backup_record_id',
        'restored_by',
        'restored_by_name',
        'restored_at',
        'targets',
        'status',
        'pre_restore_backup_id',
        'duration_seconds',
        'error',
        'metadata',
    ];

    protected $casts = [
        'restored_at' => 'datetime',
        'targets' => 'array',
        'duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    // ========================================
    // リレーション
    // ========================================

    /**
     * 復元元のバックアップ記録
     */
    public function backupRecord(): BelongsTo
    {
        return $this->belongsTo(BackupRecord::class, 'backup_record_id');
    }

    /**
     * 復元前の安全スナップショット
     */
    public function preRestoreBackup(): BelongsTo
    {
        return $this->belongsTo(BackupRecord::class, 'pre_restore_backup_id');
    }

    /**
     * 復元実行者
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'restored_by');
    }

    // ========================================
    // スコープ
    // ========================================

    /**
     * 完了した復元のみに絞り込み
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * 失敗した復元のみに絞り込み
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * 指定ユーザーが実行した復元に絞り込み
     */
    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('restored_by', $memberId);
    }

    /**
     * 指定バックアップから復元したものに絞り込み
     */
    public function scopeFromBackup(Builder $query, int $backupRecordId): Builder
    {
        return $query->where('backup_record_id', $backupRecordId);
    }

    // ========================================
    // ステータス変更メソッド
    // ========================================

    /**
     * 進行中としてマーク
     */
    public function markAsInProgress(): bool
    {
        return $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * 完了としてマーク
     */
    public function markAsCompleted(?int $durationSeconds = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    /**
     * 失敗としてマーク
     */
    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
        ]);
    }

    /**
     * ロールバック済みとしてマーク
     */
    public function markAsRolledBack(): bool
    {
        return $this->update(['status' => self::STATUS_ROLLED_BACK]);
    }

    // ========================================
    // ヘルパーメソッド
    // ========================================

    /**
     * メタデータから指定キーの値を取得
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->metadata ?? [];

        return $metadata[$key] ?? $default;
    }

    /**
     * 復元の取り消しが可能か判定
     */
    public function canRollback(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->pre_restore_backup_id !== null;
    }
}
