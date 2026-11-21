<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Unit\Support;

use App\Support\PinnedPublicKeys;
use Tests\TestCase;

/**
 * The pinned list in config/core-integrity.php is the only source of a
 * signing key's trust tier (#845).
 *
 * It used to be the key_id's prefix: a `dixlase-verified-*` or `partner-*`
 * id earned the badge of that name with nothing else required, so whoever
 * could publish a key chose its tier by choosing its name. `official` had
 * already been narrowed to pinned keys (#445). These tests hold the tier to
 * the pinned list, where it changes only in a core release.
 */
class PinnedPublicKeysTest extends TestCase
{
    /**
     * A real Ed25519 public key, so nothing here depends on an invalid one
     * being rejected elsewhere. Which key it is does not matter: this class
     * never verifies a signature, it only reports what is configured.
     */
    private const KEY = 'base64:Xn22kURy4V7xrHup/C4VmwMbr6PDeMNWe15iRZkXQ1Q=';

    /**
     * @param  array<string, mixed>  $entries
     */
    private function pin(array $entries): void
    {
        config(['core-integrity.pinned_public_keys' => $entries]);
    }

    public function test_an_unpinned_key_id_has_no_key_and_no_tier(): void
    {
        $this->pin([]);

        $this->assertNull(PinnedPublicKeys::get('partner-acme'));
        $this->assertFalse(PinnedPublicKeys::isPinned('partner-acme'));
        $this->assertNull(PinnedPublicKeys::tier('partner-acme'));
    }

    public function test_a_prefix_grants_no_tier_on_its_own(): void
    {
        $this->pin([]);

        $looksTrusted = [
            'dixlase-official-001',
            'dixlase-authority-2026',
            'dixlase-core-2026',
            'dixlase-verified-001',
            'marketplace-001',
            'partner-acme',
        ];

        foreach ($looksTrusted as $keyId) {
            $this->assertNull(
                PinnedPublicKeys::tier($keyId),
                $keyId.' is not pinned, so its name must grant nothing (#845).'
            );
        }
    }

    public function test_a_bare_string_entry_is_official(): void
    {
        $this->pin(['dixlase-authority-2026' => self::KEY]);

        $this->assertSame(self::KEY, PinnedPublicKeys::get('dixlase-authority-2026'));
        $this->assertSame('official', PinnedPublicKeys::tier('dixlase-authority-2026'));
    }

    public function test_an_array_entry_carries_its_key_and_the_tier_it_names(): void
    {
        $this->pin([
            'partner-acme-2026' => ['key' => self::KEY, 'tier' => 'partner'],
            'dixlase-verified-001' => ['key' => self::KEY, 'tier' => 'verified'],
            'some-key-2026' => ['key' => self::KEY, 'tier' => 'community'],
        ]);

        $this->assertSame(self::KEY, PinnedPublicKeys::get('partner-acme-2026'));
        $this->assertSame('partner', PinnedPublicKeys::tier('partner-acme-2026'));
        $this->assertSame('verified', PinnedPublicKeys::tier('dixlase-verified-001'));
        $this->assertSame('community', PinnedPublicKeys::tier('some-key-2026'));
    }

    public function test_an_array_entry_with_no_tier_is_official(): void
    {
        $this->pin(['dixlase-authority-2026' => ['key' => self::KEY]]);

        $this->assertSame('official', PinnedPublicKeys::tier('dixlase-authority-2026'));
    }

    public function test_a_tier_core_cannot_name_grants_nothing(): void
    {
        $this->pin([
            'typo-key' => ['key' => self::KEY, 'tier' => 'verfied'],
            'local-key' => ['key' => self::KEY, 'tier' => 'local'],
            'array-tier-key' => ['key' => self::KEY, 'tier' => ['partner']],
        ]);

        // The key is still usable for verification. Only the badge is
        // withheld, which is the safe direction: PluginTrustLevel reads a
        // null tier as Local.
        $this->assertSame(self::KEY, PinnedPublicKeys::get('typo-key'));

        $this->assertNull(PinnedPublicKeys::tier('typo-key'));
        $this->assertNull(
            PinnedPublicKeys::tier('local-key'),
            'local is what an install shows when nothing vouches for the signer, '
            .'not a tier a key can be pinned at.'
        );
        $this->assertNull(PinnedPublicKeys::tier('array-tier-key'));
    }

    public function test_an_entry_with_no_usable_key_is_not_pinned(): void
    {
        $this->pin([
            'empty-string' => '',
            'no-key' => ['tier' => 'partner'],
            'empty-key' => ['key' => '', 'tier' => 'partner'],
            'not-a-key' => 123,
        ]);

        foreach (['empty-string', 'no-key', 'empty-key', 'not-a-key'] as $keyId) {
            $this->assertFalse(
                PinnedPublicKeys::isPinned($keyId),
                $keyId.' has no key to verify with, so it is not pinned.'
            );
            $this->assertNull(
                PinnedPublicKeys::tier($keyId),
                $keyId.' must not carry a tier without a key behind it.'
            );
        }
    }

    public function test_the_key_core_ships_is_official_and_named(): void
    {
        // Not a fixture -- the list core actually ships. Every official
        // extension is signed under this key_id, so a change that stops it
        // reading as official takes the badge off all of them at once.
        $this->assertTrue(PinnedPublicKeys::isPinned('dixlase-authority-2026'));
        $this->assertSame('official', PinnedPublicKeys::tier('dixlase-authority-2026'));

        // The panel shows this name instead of the key_id, so it has to be
        // there (#1098).
        $this->assertNotNull(PinnedPublicKeys::label('dixlase-authority-2026'));
    }

    public function test_an_unpinned_key_id_has_no_label(): void
    {
        $this->pin([]);

        $this->assertNull(PinnedPublicKeys::label('partner-acme-2026'));
    }

    public function test_a_bare_string_entry_has_no_label(): void
    {
        $this->pin(['dixlase-authority-2026' => self::KEY]);

        // Nothing to show but the key_id, which is the honest fallback: the
        // claim in the sidecar is not an alternative (#1098).
        $this->assertNull(PinnedPublicKeys::label('dixlase-authority-2026'));
    }

    public function test_an_array_entry_carries_the_label_it_names(): void
    {
        $this->pin([
            'partner-acme-2026' => ['key' => self::KEY, 'tier' => 'partner', 'label' => 'Acme Ltd.'],
        ]);

        $this->assertSame('Acme Ltd.', PinnedPublicKeys::label('partner-acme-2026'));
    }

    public function test_an_unusable_label_is_no_label(): void
    {
        $this->pin([
            'empty-label' => ['key' => self::KEY, 'label' => ''],
            'array-label' => ['key' => self::KEY, 'label' => ['Acme']],
            'no-label' => ['key' => self::KEY],
        ]);

        foreach (['empty-label', 'array-label', 'no-label'] as $keyId) {
            $this->assertNull(
                PinnedPublicKeys::label($keyId),
                $keyId.' names no usable label, so the key_id is what is shown.'
            );
        }
    }
}
