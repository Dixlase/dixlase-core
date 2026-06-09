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

class MemberTwoFaRecoveryCode extends Model
{
    use HasFactory;

    protected $table = 'members_two_fa_recovery_codes';

    protected $fillable = [
        'member_id',
        'code',
        'used_at',
        'disabled',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'disabled' => 'boolean',
    ];

    /**
     * Relationship with member
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Whether the recovery code is valid
     */
    public function isValid(): bool
    {
        return ! $this->disabled && is_null($this->used_at);
    }

    /**
     * Mark as used
     */
    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    /**
     * Invalidate
     */
    public function disable(): void
    {
        $this->disabled = true;
        $this->save();
    }
}
