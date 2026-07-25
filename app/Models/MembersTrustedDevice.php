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

class MembersTrustedDevice extends Model
{
    use HasFactory;

    protected $table = 'members_trusted_devices';

    protected $fillable = [
        'member_id',
        'device_name',
        'token',
        'ip_address',
        'user_agent',
        'user_agent_hash',
        'trust_level',
        'first_ip',
        'last_ip',
        'last_used_at',
    ];

    /**
     * Model "boot" method
     * Automatically generate hash when user_agent is set
     */
    protected static function booted(): void
    {
        static::saving(function (MembersTrustedDevice $device) {
            if ($device->isDirty('user_agent') && $device->user_agent) {
                $device->user_agent_hash = hash('sha256', $device->user_agent);
            }
        });
    }

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Trust level constants
     */
    public const TRUST_LEVEL_TRUSTED = 'trusted';

    public const TRUST_LEVEL_UNKNOWN = 'unknown';

    public const TRUST_LEVEL_BLOCKED = 'blocked';

    /**
     * Relation with member
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Whether the device is trusted
     */
    public function isTrusted(): bool
    {
        return $this->trust_level === self::TRUST_LEVEL_TRUSTED;
    }

    /**
     * Whether the device is blocked
     */
    public function isBlocked(): bool
    {
        return $this->trust_level === self::TRUST_LEVEL_BLOCKED;
    }

    /**
     * Update last used datetime
     */
    public function updateLastUsed(?string $ip = null): void
    {
        $this->last_used_at = now();
        if ($ip) {
            $this->last_ip = $ip;
        }
        $this->save();
    }

    /**
     * Block device
     */
    public function block(): void
    {
        $this->trust_level = self::TRUST_LEVEL_BLOCKED;
        $this->save();
    }

    /**
     * Set device as trusted
     */
    public function trust(): void
    {
        $this->trust_level = self::TRUST_LEVEL_TRUSTED;
        $this->save();
    }
}
