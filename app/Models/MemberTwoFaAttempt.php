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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTwoFaAttempt extends Model
{
    protected $table = 'members_two_fa_attempts';

    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'attempt_type',
        'ip_address',
        'user_agent',
        'successful',
        'created_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Relation to member
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the number of failed attempts within the specified period
     */
    public static function getFailedAttemptsCount(int $memberId, int $minutes): int
    {
        return self::where('member_id', $memberId)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get the number of failed attempts for the IP address within the specified period
     */
    public static function getFailedAttemptsByIpCount(string $ipAddress, int $minutes): int
    {
        return self::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Create attempt record
     */
    public static function record(int $memberId, string $attemptType, bool $success): void
    {
        self::create([
            'member_id' => $memberId,
            'attempt_type' => $attemptType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => $success,
            'created_at' => now(),
        ]);
    }
}
