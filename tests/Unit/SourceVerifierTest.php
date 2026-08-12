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

namespace Tests\Unit;

use App\Models\ExtensionSource;
use App\Services\Extension\SourceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceVerifierTest extends TestCase
{
    use RefreshDatabase;

    protected SourceVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new SourceVerifier();
    }

    public function test_get_canonical_data_is_deterministic(): void
    {
        $source = new ExtensionSource([
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
        ]);

        $data1 = $this->verifier->getCanonicalData($source);
        $data2 = $this->verifier->getCanonicalData($source);

        $this->assertEquals($data1, $data2);

        $decoded = json_decode($data1, true);
        $this->assertEquals('github', $decoded['type']);
        $this->assertEquals('https://api.github.com', $decoded['base_url']);
        $this->assertEquals('Dixlase', $decoded['owner']);
    }

    public function test_canonical_data_keys_are_sorted(): void
    {
        $source = new ExtensionSource([
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
        ]);

        $data = $this->verifier->getCanonicalData($source);
        $keys = array_keys(json_decode($data, true));

        $this->assertEquals(['base_url', 'owner', 'type'], $keys);
    }

    public function test_verify_unsigned_source_returns_unsigned(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Unsigned',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'priority' => 0,
        ]);

        $result = $this->verifier->verify($source);

        $this->assertFalse($result['verified']);
        $this->assertEquals('unsigned', $result['status']);
    }

    public function test_sign_and_verify_round_trip(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('sodium extension required');
        }

        // Generate a test key pair
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $privateKeyBase64 = sodium_bin2base64($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL);
        $publicKeyBase64 = sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL);

        // Set the public key through config: the verifier reads
        // config('extension-sources.public_key'), not env(), so that the key
        // still resolves once an operator has run config:cache.
        config(['extension-sources.public_key' => $publicKeyBase64]);

        $source = ExtensionSource::query()->create([
            'name' => 'Sign Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
            'priority' => 0,
        ]);

        // Sign the source
        $signature = $this->verifier->sign($source, $privateKeyBase64);
        $this->assertNotEmpty($signature);

        // Store signature and verify
        $source->update(['official_signature' => $signature]);
        $source->refresh();

        $result = $this->verifier->verify($source);

        $this->assertTrue($result['verified']);
        $this->assertEquals('valid', $result['status']);

        config(['extension-sources.public_key' => null]);
    }

    public function test_verify_with_wrong_key_returns_invalid(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('sodium extension required');
        }

        // Generate two different key pairs
        $signingKeyPair = sodium_crypto_sign_keypair();
        $verifyKeyPair = sodium_crypto_sign_keypair();

        $privateKeyBase64 = sodium_bin2base64(
            sodium_crypto_sign_secretkey($signingKeyPair),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );
        $publicKeyBase64 = sodium_bin2base64(
            sodium_crypto_sign_publickey($verifyKeyPair),
            SODIUM_BASE64_VARIANT_ORIGINAL
        );

        config(['extension-sources.public_key' => $publicKeyBase64]);

        $source = ExtensionSource::query()->create([
            'name' => 'Wrong Key Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
            'priority' => 0,
        ]);

        $signature = $this->verifier->sign($source, $privateKeyBase64);
        $source->update(['official_signature' => $signature]);
        $source->refresh();

        $result = $this->verifier->verify($source);

        $this->assertFalse($result['verified']);
        $this->assertEquals('invalid', $result['status']);

        config(['extension-sources.public_key' => null]);
    }

    public function test_verify_with_no_public_key_returns_no_key(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('sodium extension required');
        }

        config(['extension-sources.public_key' => null]);

        $source = ExtensionSource::query()->create([
            'name' => 'No Key Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'official_signature' => 'some_signature',
            'priority' => 0,
        ]);

        $result = $this->verifier->verify($source);

        $this->assertFalse($result['verified']);
        $this->assertEquals('no_key', $result['status']);
    }
}
