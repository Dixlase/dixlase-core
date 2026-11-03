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
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The passkey challenge answers to the same limits as the password login.
 *
 * Every refusal that depends on the identifier answers with the generic
 * failure, each one is recorded and audited, and a locked-out identifier or
 * IP is refused before the lookup. These tests pin that shape: the challenge
 * is one of three endpoints that take an identifier from an unauthenticated
 * caller, and the three are meant to behave alike.
 */
class PasskeyChallengeProtectionsTest extends TestCase
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

        $this->mock(TwoFaHelper::class, function ($mock): void {
            $mock->shouldReceive('isMailConfigured')->andReturn(true);
        });

        // The lockout is on by default in a real install; pin it here so the
        // test does not depend on the seeder.
        SecuritySetting::setValue('login_attempt_limit_enabled', '1');
        SecuritySetting::setValue('login_attempt_max_attempts', '5');
        SecuritySetting::setValue('login_attempt_time_window', '15');
        SecuritySetting::setValue('login_attempt_lockout_duration', '30');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function passkeyService(bool $hasCredentials): void
    {
        $this->mock(TwoFaPasskeyService::class, function ($mock) use ($hasCredentials): void {
            $mock->shouldReceive('hasCredentials')->andReturn($hasCredentials);
            $mock->shouldReceive('generateLoginChallenge')->andReturn(['id' => 'c', 'publicKey' => ['challenge' => 'x']]);
        });
    }

    private function member(string $name, int $twoFaMode = 1, int $status = 1): Member
    {
        return Member::factory()->create([
            'account_name' => $name,
            'email' => $name.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => $status,
            'two_fa_mode' => $twoFaMode,
        ]);
    }

    private function challenge(string $login): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('admin.login.passkey.challenge'), ['login' => $login]);
    }

    public function test_an_unknown_identifier_and_a_member_without_a_passkey_answer_alike(): void
    {
        $this->passkeyService(false);
        $this->member('haspasskeynone');

        $unknown = $this->challenge('no-such-account');
        $known = $this->challenge('haspasskeynone');

        $this->assertSame(422, $unknown->getStatusCode());
        $this->assertSame($unknown->getStatusCode(), $known->getStatusCode());
        $this->assertSame(
            $unknown->json(),
            $known->json(),
            'The challenge must not say which identifiers exist.'
        );
    }

    public function test_a_member_with_two_factor_off_answers_like_an_unknown_identifier(): void
    {
        $this->passkeyService(true);
        // Global 2FA off and the member's own off: passkey login is refused.
        SecuritySetting::setValue('two_fa_mode', '0');
        $this->member('twofaoff', twoFaMode: 0);

        $unknown = $this->challenge('no-such-account');
        $known = $this->challenge('twofaoff');

        $this->assertSame($unknown->json(), $known->json());
    }

    public function test_a_deactivated_member_answers_like_an_unknown_identifier(): void
    {
        $this->passkeyService(true);
        $this->member('deactivated', status: 0);

        $unknown = $this->challenge('no-such-account');
        $known = $this->challenge('deactivated');

        $this->assertSame($unknown->json(), $known->json());
    }

    public function test_a_refused_challenge_is_counted_and_audited(): void
    {
        $this->passkeyService(false);
        $this->member('counted');

        $before = MemberLoginAttempt::query()->count();
        $auditBefore = AuditLog::query()->where('action', AuditLog::ACTION_PASSKEY_AUTH_FAILED)->count();

        $this->challenge('counted')->assertStatus(422);

        $this->assertGreaterThan(
            $before,
            MemberLoginAttempt::query()->count(),
            'A refused challenge must count towards the lockout.'
        );
        $this->assertGreaterThan(
            $auditBefore,
            AuditLog::query()->where('action', AuditLog::ACTION_PASSKEY_AUTH_FAILED)->count(),
            'A refused challenge must leave an audit entry; the reason lives there, not in the response.'
        );
    }

    public function test_a_locked_out_identifier_is_refused_at_the_challenge(): void
    {
        $this->passkeyService(true);
        $member = $this->member('lockedout');

        // Five failures is the limit pinned in setUp().
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt(
                $member->account_name,
                '203.0.113.10',
                'phpunit',
                false
            );
        }

        $response = $this->challenge($member->account_name);

        $response->assertStatus(422);
        $this->assertNotSame(
            true,
            $response->json('success'),
            'A locked-out identifier must not receive a challenge.'
        );
    }

    public function test_an_eligible_member_still_receives_a_challenge(): void
    {
        $this->passkeyService(true);
        $this->member('eligible');

        $this->challenge('eligible')
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
