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

namespace Tests\Unit\Services\Theme;

use App\Models\AuthorityPublicKey;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Plugin\CoreSignatureVerifier;
use App\Services\Theme\ThemeHealthScorer;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Themes are signed by the same signer as plugins (DixlaseSigner's
 * `dls:signer:sign-theme`, PluginSigner with theme.json as the manifest), but
 * core never verified them: the permission service reported "pending" as soon
 * as a signature.sig existed and derived an "official" type from a key_id the
 * theme wrote itself, and afterwards reported every theme as unsigned. Themes
 * now go through CoreSignatureVerifier like plugins.
 *
 * The fixture is signed here with a throwaway Ed25519 key pair using the same
 * canonical form as the signer, under a base path in storage/framework/testing
 * so nothing lands in the repository's real themes/ directory.
 */
class ThemeSignatureVerificationTest extends TestCase
{
    private const KEY_ID = 'dixlase-authority-test';

    private string $originalBasePath;

    private string $sandbox;

    private string $theme;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBasePath = $this->app->basePath();
        $this->sandbox = storage_path('framework/testing/theme-signature-'.uniqid());
        $this->theme = $this->sandbox.'/themes/SignedTheme';
        File::ensureDirectoryExists($this->theme.'/resources/views');
        File::put($this->theme.'/resources/views/index.blade.php', "<p>hello</p>\n");
        File::put($this->theme.'/theme.json', json_encode(['name' => 'Signed Theme', 'slug' => 'signed-theme', 'version' => '1.0.0']));
        $this->app->setBasePath($this->sandbox);

        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        $publicKey = 'base64:'.base64_encode(sodium_crypto_sign_publickey($pair));

        $this->mock(AuthorityPublicKeyResolver::class, function ($mock) use ($publicKey): void {
            $mock->shouldReceive('resolve')->andReturn(new AuthorityPublicKey(['public_key' => $publicKey]));
        });
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBasePath);
        File::deleteDirectory($this->sandbox);

        parent::tearDown();
    }

    /**
     * Sign the fixture the way PluginSigner does: files[] and a signing
     * section in the manifest, the detached signature in signature.sig.
     */
    private function sign(): void
    {
        $verifier = new class(app(AuthorityPublicKeyResolver::class)) extends CoreSignatureVerifier
        {
            /** @return array<string, string> */
            public function hashes(string $path): array
            {
                return $this->collectFileHashes($path, 'theme.json');
            }
        };

        $manifest = json_decode(File::get($this->theme.'/theme.json'), true);
        $manifest['files'] = $verifier->hashes($this->theme);
        $manifest['signing'] = ['key_id' => self::KEY_ID];

        $data = $manifest;
        unset($data['signing']);
        ksort($data);
        ksort($data['files']);
        $signature = sodium_crypto_sign_detached(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->secretKey);

        File::put($this->theme.'/theme.json', json_encode($manifest));
        File::put($this->theme.'/signature.sig', json_encode(['key_id' => self::KEY_ID, 'signature' => base64_encode($signature)]));
    }

    private function signatureStatus(): string
    {
        return app(ThemePermissionService::class)->getSignatureInfo('signed-theme')['status'];
    }

    public function test_an_unsigned_theme_is_reported_unsigned(): void
    {
        $this->assertSame('unsigned', $this->signatureStatus());
    }

    public function test_a_correctly_signed_theme_is_valid(): void
    {
        $this->sign();

        $this->assertSame('valid', $this->signatureStatus());
    }

    public function test_a_modified_file_invalidates_the_signature(): void
    {
        $this->sign();
        File::put($this->theme.'/resources/views/index.blade.php', "<p>changed</p>\n");

        $this->assertSame('invalid', $this->signatureStatus());
    }

    public function test_an_added_php_file_invalidates_the_signature(): void
    {
        $this->sign();
        File::put($this->theme.'/resources/views/extra.blade.php', "<?php echo 1; ?>\n");

        $this->assertSame('invalid', $this->signatureStatus());
    }

    /**
     * The old stub accepted any signature.sig next to a theme.json signing
     * section and called it official.
     */
    public function test_a_forged_signature_file_is_invalid(): void
    {
        $this->sign();
        File::put($this->theme.'/signature.sig', json_encode(['key_id' => self::KEY_ID, 'signature' => base64_encode(random_bytes(64))]));

        $this->assertSame('invalid', $this->signatureStatus());
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function statuses(): array
    {
        return [
            'valid' => ['valid', []],
            'unsigned' => ['unsigned', ['signature_unsigned']],
            'invalid' => ['invalid', ['signature_invalid']],
            'pending' => ['pending_verification', ['signature_pending_verification']],
            'unknown status fails closed' => ['something-new', ['signature_unsigned']],
        ];
    }

    #[DataProvider('statuses')]
    public function test_the_health_scorer_maps_each_status(string $status, array $expectedTypes): void
    {
        $permissions = $this->createStub(ThemePermissionService::class);
        $permissions->method('getSignatureInfo')->willReturn(['status' => $status]);

        $scorer = new class($permissions) extends ThemeHealthScorer
        {
            public function signatureIssues(): array
            {
                return $this->evaluateSignature('any', []);
            }
        };

        $this->assertSame($expectedTypes, array_map(fn ($issue) => $issue->type, $scorer->signatureIssues()));
    }
}
