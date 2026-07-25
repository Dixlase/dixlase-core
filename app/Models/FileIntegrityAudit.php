<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FileIntegrityAudit extends Model
{
    use HasFactory;

    protected $table = 'file_integrity_audits';

    protected $fillable = [
        'scan_uuid',
        'scope',
        'scope_identifier',
        'trigger',
        'initiated_by_type',
        'initiated_by_id',
        'status',
        'hash_algo',
        'baseline_version',
        'total_files_scanned',
        'changed_files_count',
        'added_files_count',
        'removed_files_count',
        'suspicious_files_count',
        'started_at',
        'finished_at',
        'duration_ms',
        'summary',
        'result_payload',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'result_payload' => 'array',
        'total_files_scanned' => 'integer',
        'changed_files_count' => 'integer',
        'added_files_count' => 'integer',
        'removed_files_count' => 'integer',
        'suspicious_files_count' => 'integer',
        'duration_ms' => 'integer',
    ];

    // Scope constants
    public const SCOPE_CORE = 'core';

    public const SCOPE_PLUGIN = 'plugin';

    public const SCOPE_THEME = 'theme';

    public const SCOPE_ALL = 'all';

    // Trigger constants
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_SCHEDULE = 'schedule';

    public const TRIGGER_INSTALL = 'install';

    public const TRIGGER_UPDATE = 'update';

    // Executor type constants
    public const INITIATED_BY_USER = 'user';

    public const INITIATED_BY_CLI = 'cli';

    public const INITIATED_BY_SYSTEM = 'system';

    // Status constants
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->scan_uuid)) {
                $model->scan_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation to executor (member)
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'initiated_by_id');
    }

    /**
     * Whether there is an issue
     */
    public function hasIssues(): bool
    {
        return $this->status !== self::STATUS_OK;
    }

    /**
     * Whether there is a critical issue
     */
    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * Get list of modified files
     */
    public function getChangedFiles(): array
    {
        return $this->result_payload['changed'] ?? [];
    }

    /**
     * Get list of added files
     */
    public function getAddedFiles(): array
    {
        return $this->result_payload['added'] ?? [];
    }

    /**
     * Get list of deleted files
     */
    public function getRemovedFiles(): array
    {
        return $this->result_payload['removed'] ?? [];
    }

    /**
     * Get list of suspicious files
     */
    public function getSuspiciousFiles(): array
    {
        return $this->result_payload['suspicious'] ?? [];
    }

    /**
     * Get latest scan result
     */
    public static function getLatest(?string $scope = null): ?self
    {
        $query = static::query()->orderBy('created_at', 'desc');

        if ($scope) {
            $query->where('scope', $scope);
        }

        return $query->first();
    }

    /**
     * Get latest Core scan result
     */
    public static function getLatestCore(): ?self
    {
        return static::getLatest(self::SCOPE_CORE);
    }

    /**
     * Get CSS class based on status
     */
    public function getStatusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_OK => 'text-green-600 bg-green-100',
            self::STATUS_WARNING => 'text-yellow-600 bg-yellow-100',
            self::STATUS_CRITICAL => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * Get icon based on status
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            self::STATUS_OK => 'fas fa-check-circle',
            self::STATUS_WARNING => 'fas fa-exclamation-triangle',
            self::STATUS_CRITICAL => 'fas fa-times-circle',
            default => 'fas fa-question-circle',
        };
    }

    /**
     * Get list of latest scan results by scope
     */
    public static function getLatestByScopes(): array
    {
        $scopes = [self::SCOPE_CORE, self::SCOPE_PLUGIN, self::SCOPE_THEME];
        $results = [];

        foreach ($scopes as $scope) {
            $results[$scope] = static::getLatest($scope);
        }

        return $results;
    }
}
