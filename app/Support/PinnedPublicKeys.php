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

namespace App\Support;

/**
 * Public keys compiled into core (config/core-integrity.php
 * `pinned_public_keys`) -- the root of trust for signatures.
 *
 * A pinned key_id is verified with the pinned key only; the Authority is
 * not asked, so a compromised or impersonated Authority cannot hand out a
 * different key under the same id. Only a pinned key earns the "official"
 * badge: the key_id prefix alone is a name anyone can choose (security
 * review X7). A new official key therefore ships with a core release.
 */
class PinnedPublicKeys
{
    /**
     * The pinned public key for a key_id ("base64:..." or plain base64), or
     * null when the key_id is not pinned.
     */
    public static function get(string $keyId): ?string
    {
        $pinned = (array) config('core-integrity.pinned_public_keys', []);
        $key = $pinned[$keyId] ?? null;

        return is_string($key) && $key !== '' ? $key : null;
    }

    public static function isPinned(string $keyId): bool
    {
        return self::get($keyId) !== null;
    }
}
