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

use App\Enums\PluginTrustLevel;
use Illuminate\Support\Facades\Log;

/**
 * Public keys compiled into core (config/core-integrity.php
 * `pinned_public_keys`) -- the root of trust for signatures.
 *
 * A pinned key_id is verified with the pinned key only; the Authority is
 * not asked, so a compromised or impersonated Authority cannot hand out a
 * different key under the same id.
 *
 * The list is also where a key's trust tier comes from, and the only place.
 * A key_id's prefix is a name, chosen by whoever publishes the key, so it
 * grants nothing: 'dixlase-verified-*' and 'partner-*' used to award their
 * own badge to anyone who asked (#845), and 'official' was narrowed to
 * pinned keys before them (#445, security review X7). A tier is therefore
 * something core states by shipping the key in a release.
 *
 * The same goes for the signer's display name. The one in the signature
 * sidecar is outside the signed payload, so it is a claim, not a fact
 * (#1098); an entry's 'label' is a name core ships.
 *
 * Two entry shapes:
 *
 *     'dixlase-authority-2026' => 'base64:...',                    // official
 *     'partner-acme-2026' => [
 *         'key' => 'base64:...',
 *         'tier' => 'partner',
 *         'label' => 'Acme Ltd.',
 *     ],
 */
class PinnedPublicKeys
{
    /**
     * The pinned public key for a key_id ("base64:..." or plain base64), or
     * null when the key_id is not pinned.
     */
    public static function get(string $keyId): ?string
    {
        $entry = self::entry($keyId);
        $key = is_array($entry) ? ($entry['key'] ?? null) : $entry;

        return is_string($key) && $key !== '' ? $key : null;
    }

    public static function isPinned(string $keyId): bool
    {
        return self::get($keyId) !== null;
    }

    /**
     * The trust tier a pinned key_id carries -- 'official', 'verified',
     * 'partner' or 'community' -- or null when the key_id is not pinned, or
     * names a tier core does not know.
     *
     * A bare string entry means 'official'. The pinned list changes only in
     * a core release, so shipping a key in it is the strongest statement
     * core makes about one; anything lower is written out.
     */
    public static function tier(string $keyId): ?string
    {
        if (! self::isPinned($keyId)) {
            return null;
        }

        $entry = self::entry($keyId);
        if (! is_array($entry) || ! array_key_exists('tier', $entry)) {
            return PluginTrustLevel::Official->value;
        }

        $tier = $entry['tier'];
        $level = is_string($tier) ? PluginTrustLevel::tryFrom($tier) : null;

        // Local is not a tier a key can carry -- it is what an install shows
        // when nothing vouches for the signer at all. Anything else here is
        // a typo in a core release, and the safe reading of a tier core
        // cannot name is "no tier".
        if ($level === null || $level === PluginTrustLevel::Local) {
            Log::warning('PinnedPublicKeys: pinned key names a trust tier core does not know', [
                'key_id' => $keyId,
                'tier' => is_string($tier) ? $tier : gettype($tier),
            ]);

            return null;
        }

        return $level->value;
    }

    /**
     * The display name core gives a pinned key_id, or null when the entry
     * names none -- in which case the key_id itself is what there is to show.
     *
     * This is the only signer name the admin panel shows. The sidecar's
     * 'signed_by' is outside the signed payload (#1098), so it says only
     * what the signer chose to write.
     */
    public static function label(string $keyId): ?string
    {
        if (! self::isPinned($keyId)) {
            return null;
        }

        $entry = self::entry($keyId);
        $label = is_array($entry) ? ($entry['label'] ?? null) : null;

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * The configured entry for a key_id: its key as a string, the array form,
     * or null when the key_id is absent or configured as something else.
     *
     * @return string|array<string, mixed>|null
     */
    private static function entry(string $keyId): string|array|null
    {
        $pinned = (array) config('core-integrity.pinned_public_keys', []);
        $entry = $pinned[$keyId] ?? null;

        return is_string($entry) || is_array($entry) ? $entry : null;
    }
}
