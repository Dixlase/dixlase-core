<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passkeys\Passkey as BasePasskey;

/**
 * A passkey (WebAuthn credential) registered by an admin member.
 *
 * Extends the laravel/passkeys model so the package's actions
 * (StorePasskey, VerifyPasskey) can read and write it directly. Two things
 * differ from the package default: the table is `members_passkeys`, and the
 * owner column is `member_id` instead of `user_id`.
 *
 * @property int $member_id
 * @property-read Member $member
 */
class Passkey extends BasePasskey
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members_passkeys';

    /**
     * Get the member that owns the passkey.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the owner of the passkey.
     *
     * The package reads `$passkey->user` (for example when dispatching
     * PasskeyVerified), so this points at the member relation.
     *
     * @return BelongsTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function user(): BelongsTo
    {
        // Typed as the parent declares it (see Member::passkeys()).
        /** @var BelongsTo<\Illuminate\Database\Eloquent\Model, $this> $relation */
        $relation = $this->belongsTo(Member::class, 'member_id');

        return $relation;
    }
}
