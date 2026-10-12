<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Services\Plugin;

use App\DTO\Plugin\SignatureVerificationResult;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Plugin\CoreSignatureVerifier;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The manifest's file hashes are compared whether or not a public key can be
 * resolved, because that comparison needs neither.
 *
 * The two halves of verification answer different questions: the `files` hash
 * list says the tree is the tree the manifest describes, and the Ed25519
 * signature says who signed the manifest. Only the second needs a key, so a
 * result reached without one still reports a modified tree, and `pending`
 * keeps its narrower meaning — intact files, unconfirmed signer.
 *
 * The fixture is signed with a throwaway Ed25519 key pair under a base path in
 * storage/framework/testing, so nothing lands in the repository's real
 * plugins/ directory. The key id is deliberately not one of the pinned ids, so
 * PinnedPublicKeys cannot answer and the resolver is the only route to a key.
 */
class SignatureHashOrderTest extends TestCase
{
    private const KEY_ID = 'dixlase-authority-unpinned-test';

    private string $originalBasePath;

    private string $sandbox;

    private string $plugin;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBasePath = $this->app->basePath();
        $this->sandbox = storage_path('framework/testing/signature-order-'.uniqid());
        $this->plugin = $this->sandbox.'/plugins/OrderedPlugin';

        File::ensureDirectoryExists($this->plugin.'/app');
        File::put($this->plugin.'/app/Helper.php', "<?php\n\nreturn 1;\n");
        File::put($this->plugin.'/plugin.json', json_encode([
            'name' => 'Ordered Plugin',
            'slug' => 'ordered-plugin',
            'directory' => 'OrderedPlugin',
            'version' => '1.0.0',
        ]));

        $this->app->setBasePath($this->sandbox);

        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);

        // No key is obtainable: the key id is not pinned, and the resolver
        // answers null the way it does with no network and nothing cached.
        $this->mock(AuthorityPublicKeyResolver::class, function ($mock): void {
            $mock->shouldReceive('resolve')->andReturn(null);
        });

        $this->sign();
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBasePath);
        File::deleteDirectory($this->sandbox);

        parent::tearDown();
    }

    public function test_a_modified_file_is_invalid_even_though_no_key_is_available(): void
    {
        File::put($this->plugin.'/app/Helper.php', "<?php\n\nreturn 2;\n");

        $result = app(CoreSignatureVerifier::class)->verify('ordered-plugin');

        $this->assertTrue($result->isInvalid(), 'a modified tree must be invalid, not pending');
        $this->assertNotSame(SignatureVerificationResult::STATUS_PENDING, $result->status);
    }

    public function test_an_added_file_is_invalid_even_though_no_key_is_available(): void
    {
        File::put($this->plugin.'/app/Extra.php', "<?php\n\nreturn 3;\n");

        $result = app(CoreSignatureVerifier::class)->verify('ordered-plugin');

        $this->assertTrue($result->isInvalid());
    }

    public function test_an_intact_tree_is_pending_when_no_key_is_available(): void
    {
        $result = app(CoreSignatureVerifier::class)->verify('ordered-plugin');

        $this->assertSame(SignatureVerificationResult::STATUS_PENDING, $result->status, 'intact files with no key means pending');
        $this->assertFalse($result->isInvalid());
        $this->assertSame(self::KEY_ID, $result->keyId);
    }

    /**
     * Sign the fixture the way PluginSigner does: a `files` hash list and a
     * `signing` section in the manifest, with the detached signature in
     * signature.sig.
     */
    private function sign(): void
    {
        $verifier = new class(app(AuthorityPublicKeyResolver::class)) extends CoreSignatureVerifier
        {
            /** @return array<string, string> */
            public function hashes(string $path): array
            {
                return $this->collectFileHashes($path, 'plugin.json');
            }
        };

        $manifest = json_decode(File::get($this->plugin.'/plugin.json'), true);
        $manifest['files'] = $verifier->hashes($this->plugin);
        $manifest['signing'] = ['key_id' => self::KEY_ID];

        $data = $manifest;
        unset($data['signing']);
        ksort($data);
        ksort($data['files']);
        $signature = sodium_crypto_sign_detached(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $this->secretKey,
        );

        File::put($this->plugin.'/plugin.json', json_encode($manifest));
        File::put($this->plugin.'/signature.sig', json_encode([
            'key_id' => self::KEY_ID,
            'signature' => base64_encode($signature),
        ]));
    }
}
