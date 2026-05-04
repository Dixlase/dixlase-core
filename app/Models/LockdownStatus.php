<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lockdown status model
 *
 * @property int $id
 * @property string $type
 * @property bool $is_active
 * @property string|null $reason
 * @property int|null $triggered_by
 * @property \Carbon\Carbon|null $triggered_at
 * @property int|null $released_by
 * @property \Carbon\Carbon|null $released_at
 * @property \Carbon\Carbon|null $auto_release_at
 * @property array|null $allowed_ips
 * @property array|null $allowed_members
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LockdownStatus extends Model
{
    use BelongsToSite;

    protected $table = 'lockdown_status';

    // Lockdown type
    public const TYPE_FULL = 'full';           // Block all access

    public const TYPE_ADMIN = 'admin';         // Admin panel only

    public const TYPE_API = 'api';             // API only

    public const TYPE_LOGIN = 'login';         // Login only

    protected $fillable = [
        'site_id',
        'type',
        'is_active',
        'reason',
        'triggered_by',
        'triggered_at',
        'released_by',
        'released_at',
        'auto_release_at',
        'allowed_ips',
        'allowed_members',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'triggered_at' => 'datetime',
        'released_at' => 'datetime',
        'auto_release_at' => 'datetime',
        'allowed_ips' => 'array',
        'allowed_members' => 'array',
        'metadata' => 'array',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function triggeredByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'triggered_by');
    }

    public function releasedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'released_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get currently active lockdown
     */
    public static function getActive(?string $type = null): ?self
    {
        $query = self::active();
        if ($type) {
            $query->ofType($type);
        }

        return $query->first();
    }

    /**
     * Whether lockdown is active
     */
    public static function isLocked(?string $type = null): bool
    {
        return self::getActive($type) !== null;
    }

    /**
     * Whether a specific type is locked
     */
    public static function isTypeLocked(string $type): bool
    {
        // Full lockdown affects everything
        if (self::isLocked(self::TYPE_FULL)) {
            return true;
        }

        return self::isLocked($type);
    }

    /**
     * Whether IP address is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return false;
        }

        return in_array($ip, $this->allowed_ips, true);
    }

    /**
     * Whether member is allowed
     */
    public function isMemberAllowed(int $memberId): bool
    {
        if (empty($this->allowed_members)) {
            return false;
        }

        return in_array($memberId, $this->allowed_members, true);
    }

    /**
     * Whether auto-release time has passed
     */
    public function shouldAutoRelease(): bool
    {
        if (! $this->auto_release_at) {
            return false;
        }

        return now()->gte($this->auto_release_at);
    }

    /**
     * Get lockdown type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_FULL => __('admin/lockdown.types.full'),
            self::TYPE_ADMIN => __('admin/lockdown.types.admin'),
            self::TYPE_API => __('admin/lockdown.types.api'),
            self::TYPE_LOGIN => __('admin/lockdown.types.login'),
            default => $this->type,
        };
    }

    /**
     * Get all type options
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_FULL => __('admin/lockdown.types.full'),
            self::TYPE_ADMIN => __('admin/lockdown.types.admin'),
            self::TYPE_API => __('admin/lockdown.types.api'),
            self::TYPE_LOGIN => __('admin/lockdown.types.login'),
        ];
    }
}
