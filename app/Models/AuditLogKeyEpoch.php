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

declare(strict_types=1);

namespace App\Models;

use App\Services\Security\IntegrityKeyring;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * The first audit log row an integrity key version hashed (#665).
 *
 * Written once per key version, in the same transaction as that row, and
 * signed with the version's own key. Verification reads the earliest valid
 * epoch as the point after which every chained row must be keyed: without
 * it, a rewrite could turn keyed rows back into unkeyed ones, which verify
 * with no key at all.
 *
 * @property int $id
 * @property int $key_version
 * @property int $first_log_id
 * @property string $signature
 * @property \Carbon\Carbon|null $created_at
 */
class AuditLogKeyEpoch extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_log_key_epochs';

    protected $fillable = [
        'key_version',
        'first_log_id',
        'signature',
    ];

    protected $casts = [
        'key_version' => 'integer',
        'first_log_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Record that a key version starts at the given row, unless it is
     * already recorded. Call under the chain lock.
     */
    public static function recordStart(int $keyVersion, int $firstLogId, IntegrityKeyring $keyring): void
    {
        if (self::where('key_version', $keyVersion)->exists()) {
            return;
        }

        $key = $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_EPOCH, $keyVersion);
        if ($key === null) {
            return;
        }

        self::create([
            'key_version' => $keyVersion,
            'first_log_id' => $firstLogId,
            'signature' => self::sign($keyVersion, $firstLogId, $key),
        ]);
    }

    /**
     * Whether the signature matches, using the key of this epoch's version.
     */
    public function verifySignature(IntegrityKeyring $keyring): bool
    {
        $key = $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_EPOCH, $this->key_version);

        return $key !== null
            && hash_equals(self::sign($this->key_version, $this->first_log_id, $key), (string) $this->signature);
    }

    private static function sign(int $keyVersion, int $firstLogId, string $key): string
    {
        return hash_hmac('sha256', $keyVersion.'|'.$firstLogId, $key);
    }
}
