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

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Passkey;
use App\Models\Site;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use OpenSSLAsymmetricKey;
use Tests\TestCase;

/**
 * End-to-end WebAuthn ceremonies through TwoFaPasskeyService, driven by a
 * software authenticator (an EC P-256 key held in the test) so the real
 * laravel/passkeys + web-auth/webauthn-lib validation runs: attestation
 * parsing, challenge, origin, RP ID hash, UV flag, ES256 signature, counter.
 *
 * Before this test existed no passkey test exercised a real ceremony, so a
 * library swap could break registration or login with the suite still green.
 */
class PasskeyCeremonyTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGIN = 'https://dixlase.test';

    private const RP_ID = 'dixlase.test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::ORIGIN]);
        $this->useRequest(self::ORIGIN.'/admin');
    }

    public function test_an_unknown_host_header_does_not_become_the_rp_id(): void
    {
        // A reverse proxy on another domain forwarding here with its own Host.
        $this->useRequest('https://evil.example/admin');

        $options = app(TwoFaPasskeyService::class)->generateRegistrationChallenge($this->member());

        $this->assertSame(self::RP_ID, $options['rp']['id']);
        $this->assertSame(['https://dixlase.test'], config('passkeys.allowed_origins'));
    }

    public function test_an_active_sites_host_is_used_as_the_rp_id(): void
    {
        Site::query()->create([
            'slug' => 'second',
            'name' => 'Second site',
            'host' => 'second.example',
            'primary_locale' => 'en',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);
        $this->useRequest('https://second.example/admin');

        $options = app(TwoFaPasskeyService::class)->generateRegistrationChallenge($this->member());

        $this->assertSame('second.example', $options['rp']['id']);
        $this->assertContains('https://second.example', config('passkeys.allowed_origins'));
    }

    public function test_a_member_can_register_and_then_sign_in_with_a_passkey(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();

        $passkey = $this->register($member, $authenticator, 'MacBook');

        $this->assertInstanceOf(Passkey::class, $passkey);
        $this->assertSame($member->id, $passkey->member_id);
        $this->assertSame('MacBook', $passkey->name);
        $this->assertDatabaseHas('members_passkeys', ['member_id' => $member->id, 'credential_id' => $this->b64url($authenticator['credential_id'])]);
        $this->assertTrue(app(TwoFaPasskeyService::class)->hasCredentials($member));

        $this->assertTrue($this->login($member, $authenticator, 1));

        $passkey->refresh();
        $this->assertNotNull($passkey->last_used_at);
        $this->assertSame(1, $passkey->credential['counter']);
    }

    public function test_the_registration_options_exclude_the_members_existing_passkeys(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();
        $this->register($member, $authenticator, 'First');

        $options = app(TwoFaPasskeyService::class)->generateRegistrationChallenge($member);

        $this->assertSame(
            [$this->b64url($authenticator['credential_id'])],
            array_column($options['excludeCredentials'] ?? [], 'id')
        );
    }

    public function test_the_login_options_only_allow_the_members_own_passkeys(): void
    {
        $alice = $this->member();
        $bob = $this->member();
        $aliceKey = $this->authenticator();
        $this->register($alice, $aliceKey, 'Alice');
        $this->register($bob, $this->authenticator(), 'Bob');

        $challenge = app(TwoFaPasskeyService::class)->generateLoginChallenge($alice);

        $this->assertSame(
            [$this->b64url($aliceKey['credential_id'])],
            array_column($challenge['publicKey']['allowCredentials'], 'id')
        );
    }

    public function test_a_valid_signature_from_someone_elses_passkey_is_rejected(): void
    {
        $alice = $this->member();
        $bob = $this->member();
        $bobKey = $this->authenticator();
        $this->register($alice, $this->authenticator(), 'Alice');
        $this->register($bob, $bobKey, 'Bob');

        // Bob signs Alice's challenge correctly with his own registered passkey.
        $this->assertFalse($this->login($alice, $bobKey, 1));
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();
        $this->register($member, $authenticator, 'Key');

        $this->assertFalse($this->login($member, $authenticator, 1, tamper: true));
    }

    public function test_an_assertion_from_another_origin_is_rejected(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();
        $this->register($member, $authenticator, 'Key');

        $this->assertFalse($this->login($member, $authenticator, 1, origin: 'https://evil.example'));
    }

    public function test_a_login_challenge_cannot_be_answered_twice(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();
        $this->register($member, $authenticator, 'Key');

        $service = app(TwoFaPasskeyService::class);
        $challenge = $service->generateLoginChallenge($member)['publicKey']['challenge'];
        $assertion = $this->assertion($authenticator, $challenge, 1);

        $this->assertTrue($service->verifyLoginChallenge($member, $assertion));
        $this->assertFalse($service->verifyLoginChallenge($member, $assertion));
    }

    public function test_a_challenge_issued_to_one_member_cannot_be_answered_for_another(): void
    {
        $alice = $this->member();
        $bob = $this->member();
        $bobKey = $this->authenticator();
        $this->register($bob, $bobKey, 'Bob');

        $service = app(TwoFaPasskeyService::class);
        $challenge = $service->generateLoginChallenge($alice)['publicKey']['challenge'];

        $this->assertFalse($service->verifyLoginChallenge($bob, $this->assertion($bobKey, $challenge, 1)));
    }

    public function test_the_same_authenticator_cannot_be_registered_twice(): void
    {
        $member = $this->member();
        $authenticator = $this->authenticator();
        $this->register($member, $authenticator, 'First');

        $this->expectException(InvalidPasskeyException::class);
        $this->register($member, $authenticator, 'Again');
    }

    // ------------------------------------------------------------------
    // Software authenticator
    // ------------------------------------------------------------------

    private function member(): Member
    {
        return Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    private function useRequest(string $url): void
    {
        $request = Request::create($url, 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
    }

    /**
     * @return array{key: OpenSSLAsymmetricKey, credential_id: string, x: string, y: string}
     */
    private function authenticator(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($key);

        return [
            'key' => $key,
            'credential_id' => random_bytes(32),
            'x' => str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT),
            'y' => str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT),
        ];
    }

    private function register(Member $member, array $authenticator, string $name): Passkey
    {
        $service = app(TwoFaPasskeyService::class);
        $options = $service->generateRegistrationChallenge($member);

        $this->assertSame(self::RP_ID, $options['rp']['id']);

        $clientData = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $options['challenge'],
            'origin' => self::ORIGIN,
            'crossOrigin' => false,
        ], JSON_UNESCAPED_SLASHES);

        $coseKey = $this->cborMap([
            1 => 2,                       // kty: EC2
            3 => -7,                      // alg: ES256
            -1 => 1,                      // crv: P-256
            -2 => ['bytes' => $authenticator['x']],
            -3 => ['bytes' => $authenticator['y']],
        ]);

        $authData = hash('sha256', self::RP_ID, true)
            .chr(0x01 | 0x04 | 0x40)      // UP | UV | AT
            .pack('N', 0)
            .str_repeat("\0", 16)         // AAGUID
            .pack('n', strlen($authenticator['credential_id']))
            .$authenticator['credential_id']
            .$coseKey;

        $attestationObject = $this->cborMap([
            'fmt' => 'none',
            'attStmt' => [],
            'authData' => ['bytes' => $authData],
        ]);

        return $service->registerCredential($member, [
            'id' => $this->b64url($authenticator['credential_id']),
            'rawId' => $this->b64url($authenticator['credential_id']),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->b64url($clientData),
                'attestationObject' => $this->b64url($attestationObject),
                'transports' => ['internal'],
            ],
        ], $name);
    }

    private function login(Member $member, array $authenticator, int $counter, bool $tamper = false, string $origin = self::ORIGIN): bool
    {
        $service = app(TwoFaPasskeyService::class);
        $challenge = $service->generateLoginChallenge($member)['publicKey']['challenge'];

        return $service->verifyLoginChallenge($member, $this->assertion($authenticator, $challenge, $counter, $tamper, $origin));
    }

    private function assertion(array $authenticator, string $challenge, int $counter, bool $tamper = false, string $origin = self::ORIGIN): array
    {
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $challenge,
            'origin' => $origin,
            'crossOrigin' => false,
        ], JSON_UNESCAPED_SLASHES);

        $authData = hash('sha256', self::RP_ID, true).chr(0x01 | 0x04).pack('N', $counter);

        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $authenticator['key'], OPENSSL_ALGO_SHA256);

        if ($tamper) {
            $signature[strlen($signature) - 1] = chr(ord($signature[strlen($signature) - 1]) ^ 0x01);
        }

        return [
            'id' => $this->b64url($authenticator['credential_id']),
            'rawId' => $this->b64url($authenticator['credential_id']),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->b64url($clientData),
                'authenticatorData' => $this->b64url($authData),
                'signature' => $this->b64url($signature),
            ],
        ];
    }

    private function b64url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    // Minimal CBOR encoder for the handful of types an attestation needs.

    private function cborMap(array $map): string
    {
        $out = $this->cborHead(5, count($map));
        foreach ($map as $key => $value) {
            $out .= $this->cborValue($key).$this->cborValue($value);
        }

        return $out;
    }

    private function cborValue(mixed $value): string
    {
        return match (true) {
            is_int($value) && $value >= 0 => $this->cborHead(0, $value),
            is_int($value) => $this->cborHead(1, -1 - $value),
            is_string($value) => $this->cborHead(3, strlen($value)).$value,
            is_array($value) && array_key_exists('bytes', $value) => $this->cborHead(2, strlen($value['bytes'])).$value['bytes'],
            is_array($value) => $this->cborMap($value),
        };
    }

    private function cborHead(int $major, int $length): string
    {
        $major <<= 5;

        return match (true) {
            $length < 24 => chr($major | $length),
            $length < 0x100 => chr($major | 24).chr($length),
            $length < 0x10000 => chr($major | 25).pack('n', $length),
            default => chr($major | 26).pack('N', $length),
        };
    }
}
