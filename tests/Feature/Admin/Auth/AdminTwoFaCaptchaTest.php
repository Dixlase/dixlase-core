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

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The 2FA challenge pages render the CAPTCHA widget for the `admin_two_fa`
 * form; the token they post must be verified, not just displayed.
 */
class AdminTwoFaCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

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

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secure-password-123'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => AuthenticationMode::Always->value,
        ]);

        SecuritySetting::setValue('two_fa_mode', AuthenticationMode::Always->value);
        SecuritySetting::setValue('two_fa_expire_minutes', 5);
        SecuritySetting::setValue('two_fa_max_attempts', 5);
        SecuritySetting::setValue('two_fa_attempt_window', 15);
        SecuritySetting::setValue('two_fa_lockout_duration', 30);
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        SiteSetting::setValue('mail_connection_tested', true);
        SiteSetting::setValue('mail_send_tested', true);
        SiteSetting::setValue('mail_receive_tested', true);
        SiteSetting::setValue('mail_mailer', 'log');
        SiteSetting::setValue('mail_host', 'smtp.test');
        SiteSetting::setValue('mail_port', 587);
        SiteSetting::setValue('mail_from_address', 'noreply@example.test');

        // CAPTCHA on for the 2FA page only; the login form stays uncovered so
        // the password step below does not need a token.
        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'turnstile');
        SecuritySetting::setValue('captcha_turnstile_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_turnstile_secret_key', 'test-secret-key');
        SecuritySetting::setValue('captcha_authentication_result', true);
        app(CaptchaService::class)->updateFormSetting('admin_login', false, 'turnstile');
        app(CaptchaService::class)->updateFormSetting('admin_two_fa', true, 'turnstile');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    private function loginAndSetupTwoFa(string $plainCode = '654321'): void
    {
        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        MemberTwoFaToken::where('member_id', $this->admin->id)->delete();
        MemberTwoFaToken::create([
            'member_id' => $this->admin->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    public function test_a_valid_code_with_a_rejected_captcha_token_does_not_authenticate(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => false, 'error-codes' => ['invalid-input-secret']])]);
        $this->loginAndSetupTwoFa();

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '654321',
            'cf-turnstile-response' => 'any-string',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest('member');
        Http::assertSentCount(1);
    }

    public function test_a_valid_code_with_an_accepted_captcha_token_authenticates(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);
        $this->loginAndSetupTwoFa();

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '654321',
            'cf-turnstile-response' => 'good',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_a_missing_captcha_token_is_rejected_before_the_code_is_checked(): void
    {
        Http::fake();
        $this->loginAndSetupTwoFa();

        $response = $this->post(route('admin.two-fa.email.verify'), ['code' => '654321']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest('member');
        Http::assertNothingSent();
    }
}
