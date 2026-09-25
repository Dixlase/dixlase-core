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
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Passkey login must honour the same account gates as password login.
 *
 * Password login rejects a deactivated member (LoginTrait::isAuthenticationAllowed);
 * passkey login went from a verified assertion straight to Auth::login(), so a
 * member an administrator had deactivated could still sign in with a passkey
 * they had registered earlier. It also ignored the "passkeys disabled" setting.
 *
 * The WebAuthn assertion itself is replaced with a stub that always accepts,
 * so a rejection here can only come from the account-state gate.
 */
class PasskeyLoginAccountStateTest extends TestCase
{
    use RefreshDatabase;

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
            $mock->shouldReceive('verifyLoginChallenge')->andReturn(true);
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

    private function member(int $status): Member
    {
        return Member::factory()->create([
            'account_name' => 'holder'.$status,
            'email' => "holder{$status}@example.com",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => $status,
            // Passkey login is offered only to members with 2FA enabled.
            'two_fa_mode' => 1,
        ]);
    }

    private function verifyAs(Member $member): \Illuminate\Testing\TestResponse
    {
        // Go through the challenge endpoint so the session is set up exactly
        // as it is in a real login, then answer it.
        $this->postJson(route('admin.login.passkey.challenge'), ['login' => $member->account_name]);

        return $this->postJson(route('admin.login.passkey.verify'), ['id' => 'x']);
    }

    public function test_the_challenge_is_refused_for_a_deactivated_member(): void
    {
        $member = $this->member(0);

        $this->postJson(route('admin.login.passkey.challenge'), ['login' => $member->account_name])
            ->assertStatus(422);
    }

    public function test_an_active_member_logs_in_with_a_verified_passkey(): void
    {
        $member = $this->member(1);

        $this->verifyAs($member)->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Auth::guard('member')->check());
    }

    public function test_a_deactivated_member_cannot_log_in_with_a_passkey(): void
    {
        $member = $this->member(0);

        $this->verifyAs($member)->assertStatus(422);

        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_passkey_login_is_refused_when_passkeys_are_disabled(): void
    {
        SecuritySetting::setValue('two_fa_passkey_mode', '0');
        $member = $this->member(1);

        $this->verifyAs($member)->assertStatus(422);

        $this->assertFalse(Auth::guard('member')->check());
    }
}
