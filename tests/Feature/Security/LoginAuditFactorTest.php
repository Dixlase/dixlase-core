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

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Helpers\TwoFaHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Mail\TwoFaCodeMail;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The login audit row has to say what authenticated the member.
 *
 * Core has four paths that end in Auth::login(), and all four used to write
 * an identical `login` row: the framework's Login event carries the user, the
 * guard and the remember flag, and no notion of a factor. Reading the log,
 * an operator could not tell a password-only session from one that went
 * through a second factor -- so the record could not answer the question the
 * rest of the security story is checked against.
 *
 * Each path now names its factor on the audit service immediately before
 * logging in, and the subscriber records and clears it.
 */
class LoginAuditFactorTest extends TestCase
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

        Mail::fake();
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function member(array $overrides = []): Member
    {
        return Member::factory()->create($overrides + [
            'account_name' => 'holder',
            'email' => 'holder@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
        ]);
    }

    /**
     * The password step only hands over to a second factor when the mail
     * server has been tested, so a 2FA test has to say it was.
     */
    private function allowTwoFa(): void
    {
        // Always, so the second factor is required regardless of device.
        SecuritySetting::setValue('two_fa_mode', (string) AuthenticationMode::Always->value);

        SiteSetting::setValue('mail_connection_tested', true);
        SiteSetting::setValue('mail_send_tested', true);
        SiteSetting::setValue('mail_receive_tested', true);

        // A partial mock: only the mail-configuration probe is answered,
        // and everything else -- isTwoFaEnabled() among them -- runs for
        // real, because the 2FA decision is what is under test.
        $this->partialMock(TwoFaHelper::class, function ($mock): void {
            $mock->shouldReceive('isMailConfigured')->andReturn(true);
        });
    }

    /**
     * Run the password step for real, so the hand-over session is written by
     * the application rather than seeded. The admin area swaps in its own
     * session table, so a seeded session is not the one the request reads.
     */
    private function passwordStep(Member $member): void
    {
        $this->post(route('admin.login.store'), [
            'login' => $member->account_name,
            'password' => 'password',
        ]);
    }

    /**
     * The factor on the most recent successful login row, or the sentinel
     * when the row carries none.
     */
    private function recordedFactor(string $missing = '__absent__'): string
    {
        $row = AuditLog::query()
            ->where('action', AuditLog::ACTION_LOGIN)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($row, 'A successful login is expected to write an audit row.');

        return $row->context['auth_factor'] ?? $missing;
    }

    private function loginRowCount(): int
    {
        return AuditLog::query()->where('action', AuditLog::ACTION_LOGIN)->count();
    }

    public function test_a_password_login_records_the_password_factor(): void
    {
        // 2FA off, so the password is the whole authentication.
        $member = $this->member(['two_fa_mode' => 0]);

        $this->post(route('admin.login.store'), [
            'login' => $member->account_name,
            'password' => 'password',
        ]);

        $this->assertTrue(Auth::guard('member')->check(), 'The password login is expected to succeed.');
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSWORD, $this->recordedFactor());
    }

    public function test_a_passkey_login_records_the_passkey_factor(): void
    {
        // The assertion is stubbed; what is under test is the recorded factor.
        $this->mock(TwoFaPasskeyService::class, function ($mock): void {
            $mock->shouldReceive('verifyLoginChallenge')->andReturn(true);
            $mock->shouldReceive('hasCredentials')->andReturn(true);
            $mock->shouldReceive('generateLoginChallenge')->andReturn(['id' => 'c', 'publicKey' => []]);
        });
        $this->mock(TwoFaHelper::class, function ($mock): void {
            $mock->shouldReceive('isMailConfigured')->andReturn(true);
        });

        // Passkey login is offered only to members with 2FA enabled.
        $member = $this->member(['two_fa_mode' => 1]);

        $this->postJson(route('admin.login.passkey.challenge'), ['login' => $member->account_name]);
        $this->postJson(route('admin.login.passkey.verify'), ['id' => 'x'])->assertOk();

        $this->assertTrue(Auth::guard('member')->check());
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSKEY, $this->recordedFactor());
    }

    /**
     * The second-factor paths are entered with the password step already
     * done, which is what the session carries. Seeding it goes straight to
     * the step under test instead of replaying the password form.
     */
    public function test_an_email_code_login_records_the_password_and_email_code_factor(): void
    {
        $this->allowTwoFa();
        $member = $this->member(['two_fa_mode' => 1]);

        // The password step sends the code; read it back off the mailable
        // rather than reaching into the hashed token row.
        $this->passwordStep($member);
        $this->assertFalse(Auth::guard('member')->check(), 'The password alone must not sign the member in.');

        $code = null;
        Mail::assertSent(TwoFaCodeMail::class, function (TwoFaCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code, 'The password step is expected to send a code.');

        $this->post(route('admin.two-fa.email.verify'), ['code' => $code]);

        $this->assertTrue(Auth::guard('member')->check(), 'The e-mail code is expected to be accepted.');
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSWORD_EMAIL_CODE, $this->recordedFactor());
    }

    public function test_a_recovery_code_login_records_the_password_and_recovery_code_factor(): void
    {
        $this->allowTwoFa();
        $member = $this->member(['two_fa_mode' => 1]);

        $codes = app(TwoFaRecoveryCodeService::class)->generate($member);
        $this->assertNotEmpty($codes, 'Recovery codes are expected to be generated.');

        $this->passwordStep($member);
        $this->assertFalse(Auth::guard('member')->check(), 'The password alone must not sign the member in.');

        $this->post(route('admin.two-fa.recovery-code.confirm'), ['recovery_code' => $codes[0]]);

        $this->assertTrue(Auth::guard('member')->check(), 'The recovery code is expected to be accepted.');
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSWORD_RECOVERY_CODE, $this->recordedFactor());
    }

    /**
     * A login nothing named a factor for -- a console command, a test helper
     * calling Auth::login() directly -- records no factor rather than a
     * guessed one. An absent key is honest; a wrong one is worse than none.
     */
    public function test_a_login_that_names_no_factor_records_none(): void
    {
        $member = $this->member();

        Auth::guard('member')->login($member);

        $this->assertSame('__absent__', $this->recordedFactor());
    }

    /**
     * The value lives on a singleton for the length of the request, so the
     * subscriber has to clear it. Otherwise one named login would stamp its
     * factor on every later login row in the same request.
     */
    public function test_the_factor_does_not_carry_into_a_later_login_row(): void
    {
        $member = $this->member(['two_fa_mode' => 0]);

        $this->post(route('admin.login.store'), [
            'login' => $member->account_name,
            'password' => 'password',
        ]);
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSWORD, $this->recordedFactor());

        $before = $this->loginRowCount();

        // A second login in the same process, with nothing naming a factor.
        Auth::guard('member')->login($member);

        $this->assertSame($before + 1, $this->loginRowCount(), 'The second login is expected to write its own row.');
        $this->assertSame('__absent__', $this->recordedFactor());
    }

    /**
     * Guard against the factor being recorded for a login that did not
     * happen: a refused second factor must leave no login row at all.
     */
    public function test_a_refused_email_code_records_no_login_row(): void
    {
        $this->allowTwoFa();
        $member = $this->member(['two_fa_mode' => 1]);

        $this->passwordStep($member);

        $this->post(route('admin.two-fa.email.verify'), ['code' => '000000']);

        $this->assertFalse(Auth::guard('member')->check());
        $this->assertSame(0, $this->loginRowCount());
    }

    /**
     * The factor is added to the row, not swapped in for what was there.
     */
    public function test_the_existing_context_keys_are_kept(): void
    {
        $member = $this->member(['two_fa_mode' => 0]);

        $this->post(route('admin.login.store'), [
            'login' => $member->account_name,
            'password' => 'password',
            'remember' => '1',
        ]);

        $row = AuditLog::query()
            ->where('action', AuditLog::ACTION_LOGIN)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('member', $row->context['guard'] ?? null);
        $this->assertTrue($row->context['remember'] ?? false);
        $this->assertSame(AuditLog::AUTH_FACTOR_PASSWORD, $row->context['auth_factor'] ?? null);
        $this->assertArrayHasKey('message', $row->context);
    }
}
