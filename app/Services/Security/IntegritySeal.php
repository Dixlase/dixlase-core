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

namespace App\Services\Security;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Seals a piece of data that core writes to disk and later trusts
 * (dixlase-core#803), with a key derived from the integrity key.
 *
 * A seal is `['key_version' => int, 'mac' => hex]`. It shows that the data
 * was written by something holding the integrity key. It does not stop code
 * running inside the application, which can read the same `.env`; it stops
 * a party that can only write files — a file-write bug, a restored archive,
 * a sync into `storage/` — from producing data core will accept.
 */
class IntegritySeal
{
    /** The seal matches */
    public const VALID = 'valid';

    /** The data carries no seal */
    public const UNSEALED = 'unsealed';

    /** The seal does not match the data, or is malformed */
    public const INVALID = 'invalid';

    /** The seal names a key version that is not configured */
    public const KEY_UNAVAILABLE = 'key_unavailable';

    public function __construct(private IntegrityKeyring $keyring) {}

    public function keyring(): IntegrityKeyring
    {
        return $this->keyring;
    }

    /**
     * Seal a message with the current key, or null when no key is configured.
     *
     * @return array{key_version:int, mac:string}|null
     */
    public function sign(string $purpose, string $message): ?array
    {
        $current = $this->keyring->currentKey($purpose);
        if ($current === null) {
            return null;
        }

        return [
            'key_version' => $current['version'],
            'mac' => hash_hmac('sha256', $message, $current['key']),
        ];
    }

    /**
     * Check a seal against a message.
     *
     * @param  mixed  $seal  What was stored as the seal; anything not shaped like one is INVALID
     * @return self::VALID|self::UNSEALED|self::INVALID|self::KEY_UNAVAILABLE
     */
    public function check(string $purpose, string $message, mixed $seal): string
    {
        if ($seal === null) {
            return self::UNSEALED;
        }

        if (! is_array($seal) || ! is_int($seal['key_version'] ?? null) || ! is_string($seal['mac'] ?? null)) {
            return self::INVALID;
        }

        $key = $this->keyring->key($purpose, $seal['key_version']);
        if ($key === null) {
            return self::KEY_UNAVAILABLE;
        }

        return hash_equals(hash_hmac('sha256', $message, $key), $seal['mac']) ? self::VALID : self::INVALID;
    }
}
