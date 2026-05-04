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
 * Restore record model
 *
 * Manages audit trails for restore operations from backups.
 * Records who restored what, when, and from which backup.
 */
class RestoreRecord extends Model
{
    // ========================================
    // Status constants
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
    // Relations
    // ========================================

    /**
     * Source backup record
     */
    public function backupRecord(): BelongsTo
    {
        return $this->belongsTo(BackupRecord::class, 'backup_record_id');
    }

    /**
     * Safety snapshot before restore
     */
    public function preRestoreBackup(): BelongsTo
    {
        return $this->belongsTo(BackupRecord::class, 'pre_restore_backup_id');
    }

    /**
     * User who executed the restore
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'restored_by');
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Filter to completed restores only
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Filter to failed restores only
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Filter to restores executed by specified user
     */
    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('restored_by', $memberId);
    }

    /**
     * Filter to restores from specified backup
     */
    public function scopeFromBackup(Builder $query, int $backupRecordId): Builder
    {
        return $query->where('backup_record_id', $backupRecordId);
    }

    // ========================================
    // Status change methods
    // ========================================

    /**
     * Mark as in progress
     */
    public function markAsInProgress(): bool
    {
        return $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?int $durationSeconds = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
        ]);
    }

    /**
     * Mark as rolled back
     */
    public function markAsRolledBack(): bool
    {
        return $this->update(['status' => self::STATUS_ROLLED_BACK]);
    }

    // ========================================
    // Helper methods
    // ========================================

    /**
     * Get value for specified key from metadata
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->metadata ?? [];

        return $metadata[$key] ?? $default;
    }

    /**
     * Determine if the restore can be cancelled
     */
    public function canRollback(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->pre_restore_backup_id !== null;
    }
}
