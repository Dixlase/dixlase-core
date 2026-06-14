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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
 * Backup record model
 *
 * Manages metadata for backups created by backup plugins
 */
class BackupRecord extends Model
{
    // ========================================
    // Status constants
    // ========================================

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DELETED = 'deleted';

    // ========================================
    // Verification status constants
    // ========================================

    public const VERIFICATION_UNCHECKED = 'unchecked';

    public const VERIFICATION_VALID = 'valid';

    public const VERIFICATION_INVALID = 'invalid';

    // ========================================
    // Type constants
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
        'note',
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
    // Scopes
    // ========================================

    /**
     * Filter backups by specified plugin
     */
    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    /**
     * Filter encrypted backups
     */
    public function scopeEncrypted(Builder $query): Builder
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Filter verified (valid) backups
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('verification_status', self::VERIFICATION_VALID);
    }

    /**
     * Filter backups past retention period
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('retention_until', '<=', now());
    }

    /**
     * Filter by backup type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ========================================
    // Status change methods
    // ========================================

    /**
     * Mark as verified
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'verification_status' => self::VERIFICATION_VALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Mark as verification failed
     */
    public function markAsInvalid(): bool
    {
        return $this->update([
            'verification_status' => self::VERIFICATION_INVALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Mark as deleted
     */
    public function markAsDeleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELETED,
        ]);
    }

    // ========================================
    // Helper methods
    // ========================================

    /**
     * Determine if past retention period
     */
    public function isExpired(): bool
    {
        if ($this->retention_until === null) {
            return false;
        }

        return $this->retention_until->isPast();
    }

    /**
     * Get value for specified key from metadata
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        $metadata = $this->metadata ?? [];

        return $metadata[$key] ?? $default;
    }
}
