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

namespace Tests\Unit\Services\Core;

use App\Contracts\Core\CoreManifestBuilderInterface;
use App\Services\Core\CoreIntegrityVerifier;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Signature\SignatureWaiverService;
use Tests\TestCase;

/**
 * Core's own badge reads the same pinned list as an extension's (#845).
 *
 * This verifier carried its own copy of the prefix test the plugin verifier
 * had -- the same three prefixes, plus `dixlase-core`. Both now ask
 * PinnedPublicKeys, so there is one list and one answer. The copy is why
 * this test exists: the defect was fixed in the plugin verifier first
 * (#445, for `official`) and the copy here was not touched.
 */
class CoreIntegrityVerifierTypeTest extends TestCase
{
    private function tierFor(string $keyId): ?string
    {
        // Stubs, not mocks: determineType() calls none of them. They are
        // here only because the constructor asks for them.
        $verifier = new CoreIntegrityVerifier(
            $this->createStub(AuthorityPublicKeyResolver::class),
            $this->createStub(CoreManifestBuilderInterface::class),
            $this->createStub(SignatureWaiverService::class),
        );

        $method = new \ReflectionMethod($verifier, 'determineType');

        return $method->invoke($verifier, $keyId);
    }

    public function test_an_unpinned_key_id_gets_no_tier_whatever_it_is_called(): void
    {
        config(['core-integrity.pinned_public_keys' => []]);

        foreach (['dixlase-core-2026', 'dixlase-official-001', 'dixlase-verified-001', 'marketplace-001', 'partner-acme'] as $keyId) {
            $this->assertNull(
                $this->tierFor($keyId),
                $keyId.' is not pinned, so its name must grant nothing (#845).'
            );
        }
    }

    public function test_a_pinned_core_key_is_official(): void
    {
        $key = 'base64:'.base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        config(['core-integrity.pinned_public_keys' => ['dixlase-core-2026' => $key]]);

        $this->assertSame('official', $this->tierFor('dixlase-core-2026'));
        $this->assertNull($this->tierFor('dixlase-core-2027'));
    }

    public function test_a_pinned_key_carries_the_tier_its_entry_names(): void
    {
        $key = 'base64:'.base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        config(['core-integrity.pinned_public_keys' => [
            'partner-acme-2026' => ['key' => $key, 'tier' => 'partner'],
        ]]);

        $this->assertSame('partner', $this->tierFor('partner-acme-2026'));
    }
}
