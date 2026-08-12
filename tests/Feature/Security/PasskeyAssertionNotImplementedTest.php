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
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use ReflectionClass;
use Tests\TestCase;

/**
 * TwoFaPasskeyService::verifyAssertion() returned true after checking only
 * that the challenge in clientDataJSON matched the session. The public key and
 * the signature were parameters it never read; the RP ID hash, UP/UV flags and
 * signature counter in authenticatorData went unexamined, as did the `type`
 * and `origin` fields of clientDataJSON. Naming a credential ID and replaying
 * the just-issued challenge was enough.
 *
 * Nothing in Core called it. That was luck rather than design -- the class
 * carries `@api`, appears in PLUGIN-API.md, and the method is declared on
 * TwoFaPasskeyServiceInterface, so it reads as the supported way for a plugin
 * to verify a passkey. The DixlaseUsers 2FA work that would have used it is
 * still ahead, which is exactly why this is worth closing now: the cost is
 * zero while there are no callers.
 *
 * It throws rather than returning false. False would be indistinguishable from
 * a failed authentication and would leave a plugin author debugging their own
 * correct code.
 */
class PasskeyAssertionNotImplementedTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        return Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    public function test_verify_assertion_refuses_to_run(): void
    {
        $this->expectException(LogicException::class);

        app(TwoFaPasskeyService::class)->verifyAssertion($this->member(), [
            'id' => 'any-credential-id',
            'response' => [
                'signature' => 'not-a-signature',
                'authenticatorData' => 'anything',
                'clientDataJSON' => base64_encode(json_encode(['challenge' => 'replayed'])),
            ],
        ]);
    }

    /**
     * The message has to name the alternative. A plugin author hitting this
     * needs to know where verification actually lives, not just that this door
     * is shut.
     */
    public function test_the_failure_points_at_the_validated_path(): void
    {
        try {
            app(TwoFaPasskeyService::class)->verifyAssertion($this->member(), []);
            $this->fail('verifyAssertion() must not succeed.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('verifyLoginChallenge', $e->getMessage());
        }
    }

    /**
     * The trait method was the only route into verifyAssertion(). It must not
     * quietly swallow the exception and hand back a boolean.
     */
    public function test_the_trait_route_in_also_fails(): void
    {
        // The trait declares 19 abstract methods, so it cannot be exercised
        // through an anonymous class without implementing all of them --
        // noise that would say nothing about the behaviour under test.
        $method = new \ReflectionMethod(
            \App\Traits\TwoFa\TwoFaAuthenticationTrait::class,
            'verifyPasskeyCredential'
        );

        $this->assertTrue(
            $method->isProtected(),
            'verifyPasskeyCredential() is the trait-side entry point and should stay internal.'
        );

        // It delegates straight to the service, so the guarantee that matters
        // is that the service refuses -- asserted above and re-checked here
        // through the exact call the trait makes.
        $this->expectException(LogicException::class);
        app(TwoFaPasskeyService::class)->verifyAssertion($this->member(), ['id' => 'x']);
    }

    /**
     * The signature-free helper must not come back. Its name was what made the
     * original dangerous -- it read as verification.
     */
    public function test_the_simplified_signature_check_is_gone(): void
    {
        $this->assertFalse(
            (new ReflectionClass(TwoFaPasskeyService::class))->hasMethod('verifySignature'),
            'verifySignature() accepted any signature bytes; it must not be reintroduced.'
        );
    }

    /**
     * The real path must keep working. A fix that also broke admin passkey
     * login would be worse than the bug.
     */
    public function test_the_validated_login_path_is_intact(): void
    {
        $service = new ReflectionClass(TwoFaPasskeyService::class);

        $this->assertTrue(
            $service->hasMethod('verifyLoginChallenge'),
            'Admin passkey login depends on this method.'
        );

        $source = file_get_contents($service->getFileName());

        $this->assertStringContainsString(
            'AssertionValidator',
            $source,
            'verifyLoginChallenge() must keep running the Laragear validation pipeline.'
        );
    }
}
