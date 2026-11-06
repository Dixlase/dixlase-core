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
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\MemberTwoFaRecoveryCode;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\TwoFa\TwoFaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The condition under which a password login asks for the second step.
 *
 * The second step is asked for when the account has it enabled and a step can
 * actually be completed for it. The mail server's test state does not describe
 * that on its own: the e-mailed code needs it, the recovery code does not. The
 * state is site-wide and returns to unset whenever the mail settings are saved
 * with a value changed, so reading it as the whole condition made an unrelated
 * settings change decide whether every account on the site had a second step.
 */
class TwoFaSecondStepConditionTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        View::addNamespace('admin', [
            resource_path('views/'.config('themes.admin_theme', 'admin')),
        ]);

        $this->admin = Member::create([
            'account_name' => 'stepadmin',
            'display_name' => 'Step Admin',
            'email' => 'step-admin@example.com',
            'password' => Hash::make('secure-password-123'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => AuthenticationMode::Always->value,
        ]);

        SecuritySetting::setValue('two_fa_mode', AuthenticationMode::Always->value);
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        SiteSetting::setValue('mail_mailer', 'log');
        SiteSetting::setValue('mail_host', 'smtp.test');
        SiteSetting::setValue('mail_port', 587);
        SiteSetting::setValue('mail_from_address', 'noreply@example.test');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    private function setMailTested(bool $tested): void
    {
        SiteSetting::setValue('mail_connection_tested', $tested);
        SiteSetting::setValue('mail_send_tested', $tested);
        SiteSetting::setValue('mail_receive_tested', $tested);
    }

    private function giveRecoveryCode(): void
    {
        MemberTwoFaRecoveryCode::create([
            'member_id' => $this->admin->id,
            'code' => Hash::make('11111-22222-33333-44444'),
        ]);
    }

    private function attemptLogin(): void
    {
        $this->post(route('admin.login.store'), [
            'login' => 'step-admin@example.com',
            'password' => 'secure-password-123',
        ]);
    }

    private function assertSecondStepAsked(): void
    {
        $this->assertGuest('member');
        $this->assertTrue(session()->has('login.id'), 'the login is waiting for a second step');
    }

    public function test_a_tested_mail_server_asks_for_the_second_step(): void
    {
        $this->setMailTested(true);

        $this->attemptLogin();

        $this->assertSecondStepAsked();
    }

    public function test_a_recovery_code_keeps_the_second_step_without_a_tested_mail_server(): void
    {
        $this->giveRecoveryCode();
        $this->setMailTested(false);

        $this->attemptLogin();

        $this->assertSecondStepAsked();
    }

    public function test_an_account_with_no_completable_step_is_let_through(): void
    {
        $this->setMailTested(false);

        $this->attemptLogin();

        // Refusing it would lock out every account on a site whose mail server
        // is down, including the one that would repair it. The log records it.
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_the_account_setting_is_never_changed_by_the_mail_state(): void
    {
        $this->setMailTested(false);

        $this->attemptLogin();

        $this->assertSame(
            AuthenticationMode::Always->value,
            $this->admin->fresh()->two_fa_mode->value,
        );
    }

    public function test_can_challenge_follows_the_recovery_code_not_the_mail_state(): void
    {
        $service = app(TwoFaService::class, [
            'settingModelClass' => SecuritySetting::class,
            'context' => 'admin',
        ]);

        $this->setMailTested(false);
        $this->assertFalse($service->canChallenge($this->admin));
        $this->assertFalse($service->hasUsableRecoveryCode($this->admin));

        $this->giveRecoveryCode();
        $this->assertTrue($service->canChallenge($this->admin->fresh()));
        $this->assertTrue($service->hasUsableRecoveryCode($this->admin->fresh()));

        $this->setMailTested(true);
        $this->assertTrue($service->canChallenge($this->admin->fresh()));
    }

    public function test_a_used_recovery_code_does_not_count(): void
    {
        MemberTwoFaRecoveryCode::create([
            'member_id' => $this->admin->id,
            'code' => Hash::make('55555-66666-77777-88888'),
            'used_at' => now(),
        ]);

        $service = app(TwoFaService::class, [
            'settingModelClass' => SecuritySetting::class,
            'context' => 'admin',
        ]);

        $this->setMailTested(false);

        $this->assertFalse($service->hasUsableRecoveryCode($this->admin));
        $this->assertFalse($service->canChallenge($this->admin));
    }
}
