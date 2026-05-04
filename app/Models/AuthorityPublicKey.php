<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal コア専用。プラグイン/テーマから参照しないこと
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

use Illuminate\Database\Eloquent\Model;

/**
 * Local cache of Authority public keys
 *
 * Stores public keys retrieved from keys.dixlase.com for use in
 * signature verification during plugin installation. The Resolver determines whether to re-fetch keys with old fetched_at values
 */
class AuthorityPublicKey extends Model
{
    protected $table = 'authority_public_keys';

    protected $fillable = [
        'key_id',
        'public_key',
        'algorithm',
        'is_active',
        'expires_at',
        'authority_created_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'authority_created_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Returns true if the specified time has elapsed since fetched_at (re-fetch recommended)
     */
    public function isStale(int $ttlHours): bool
    {
        return $this->fetched_at->lt(now()->subHours($ttlHours));
    }
}
