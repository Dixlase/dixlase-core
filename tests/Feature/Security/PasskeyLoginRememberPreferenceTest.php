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
use App\Helpers\TwoFaHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Passkey login must honour the member's "remember me" choice.
 *
 * It used to pass the flag as a literal true, so a member who deliberately
 * left the box unticked still got a persistent recaller cookie -- and that
 * cookie outlives the session by a long way, which is the opposite of what
 * unticking it asks for. Password login and 2FA completion both read the
 * choice; this path overrode it, so what the checkbox meant depended on which
 * button was pressed.
 *
 * The WebAuthn assertion is replaced with a stub that always accepts, so the
 * cookie these tests look at can only come from the remember decision.
 */
class PasskeyLoginRememberPreferenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The array the trait handed to the assertion verifier on the last call.
     *
     * @var array<string, mixed>|null
     */
    private ?array $assertionData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $this->mock(TwoFaPasskeyService::class, function ($mock): void {
            $mock->shouldReceive('verifyLoginChallenge')
                ->andReturnUsing(function ($user, array $data): bool {
                    $this->assertionData = $data;

                    return true;
                });
            $mock->shouldReceive('hasCredentials')->andReturn(true);
            $mock->shouldReceive('generateLoginChallenge')->andReturn(['id' => 'c', 'publicKey' => []]);
        });

        $this->mock(TwoFaHelper::class, function ($mock): void {
            $mock->shouldReceive('isMailConfigured')->andReturn(true);
        });
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function member(): Member
    {
        return Member::factory()->create([
            'account_name' => 'holder',
            'email' => 'holder@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
            // Passkey login is offered only to members with 2FA enabled.
            'two_fa_mode' => 1,
        ]);
    }

    /**
     * The cookie that keeps a member signed in past the session's lifetime.
     * Laravel derives the name from the guard, so ask the guard for it rather
     * than spelling out the hash.
     */
    private function recallerName(): string
    {
        return Auth::guard('member')->getRecallerName();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function verifyAs(Member $member, array $payload): \Illuminate\Testing\TestResponse
    {
        // Go through the challenge endpoint so the session is set up exactly
        // as it is in a real login, then answer it.
        $this->postJson(route('admin.login.passkey.challenge'), ['login' => $member->account_name]);

        return $this->postJson(route('admin.login.passkey.verify'), $payload + ['id' => 'x']);
    }

    public function test_asking_to_be_remembered_sets_the_persistent_cookie(): void
    {
        $member = $this->member();

        $this->verifyAs($member, ['remember' => true])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertCookie($this->recallerName());

        $this->assertTrue(Auth::guard('member')->check());
    }

    public function test_declining_to_be_remembered_sets_no_persistent_cookie(): void
    {
        $member = $this->member();

        $this->verifyAs($member, ['remember' => false])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertCookieMissing($this->recallerName());

        // The member is signed in either way; only the cookie differs.
        $this->assertTrue(Auth::guard('member')->check());
    }

    /**
     * A client that sends nothing gets the session-only login. The safer
     * behaviour has to be the default, or an older front end would quietly
     * keep the old one.
     */
    public function test_omitting_the_field_sets_no_persistent_cookie(): void
    {
        $member = $this->member();

        $this->verifyAs($member, [])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertCookieMissing($this->recallerName());

        $this->assertTrue(Auth::guard('member')->check());
    }

    /**
     * The request body is deserialized into a PublicKeyCredential, so the
     * remember field has to be taken out before it gets there. Passing the
     * whole request through would feed the parser a field it never expects.
     */
    public function test_the_remember_field_is_kept_out_of_the_assertion(): void
    {
        $member = $this->member();

        $this->verifyAs($member, ['remember' => true])->assertOk();

        $this->assertIsArray($this->assertionData, 'The assertion verifier is expected to be called.');
        $this->assertArrayNotHasKey('remember', $this->assertionData);
        $this->assertArrayHasKey('id', $this->assertionData, 'The assertion itself must still be passed through.');
    }

    /**
     * The defect was an inconsistency between the three paths, so the value is
     * in none of them going back to a literal.
     *
     * @return array<string, array{0: string}>
     */
    public static function authenticationPaths(): array
    {
        return [
            'password login' => ['app/Traits/LoginTrait.php'],
            '2FA completion' => ['app/Traits/TwoFa/TwoFaAuthenticationTrait.php'],
            'passkey login' => ['app/Traits/PasskeyLoginTrait.php'],
        ];
    }

    #[DataProvider('authenticationPaths')]
    public function test_no_authentication_path_hard_codes_the_remember_flag(string $path): void
    {
        $this->assertStringNotContainsString(
            '->login($user, true)',
            file_get_contents(base_path($path)),
            "{$path} must take the remember flag from the member's choice, not pass a literal."
        );
    }
}
