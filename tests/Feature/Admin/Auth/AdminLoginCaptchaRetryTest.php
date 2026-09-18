<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Tests\Feature\Admin\Auth;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The two-step login solves the CAPTCHA on the identifier step and carries
 * the result to the password step through a five-minute session flag; the
 * password step renders no widget of its own. LoginTrait::store() used to
 * clear that flag before checking the password, so one wrong password
 * consumed it and the retry — with no flag and no widget to get a token from
 * — failed with "CAPTCHA token is missing" whatever was typed. On dixlase.org
 * that meant a single typo locked the admin out until the page was reloaded
 * from step one.
 *
 * Turnstile is used here because its driver answers "token is missing" from
 * the request alone, without calling the provider.
 */
class AdminLoginCaptchaRetryTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN = 'admin@example.com';

    private const PASSWORD = 'secure-password-123';

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

        Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => self::LOGIN,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        // CAPTCHA on, Turnstile, admin login form covered.
        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'turnstile');
        SecuritySetting::setValue('captcha_turnstile_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_turnstile_secret_key', 'test-secret-key');
        SecuritySetting::setValue('captcha_authentication_result', true);
        app(\App\Services\CaptchaService::class)->updateFormSetting('admin_login', true, 'turnstile');

        // ApplySessionConfig rewrites session.driver from the stored settings
        // on every request, so a session seeded with withSession() before
        // that point lands in a different store than the request reads.
        // Apply the same config first so the seeded flag is the one
        // LoginTrait::store() sees.
        \App\Helpers\ConfigHelper::applySessionConfig('member');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_a_wrong_password_does_not_consume_the_identifier_step_captcha(): void
    {
        $this->withSession([$this->flagKey() => time()]);

        $wrong = $this->post(route('admin.login.store'), [
            'login' => self::LOGIN,
            'password' => 'not-the-password',
        ]);

        $wrong->assertSessionHas('error');
        $wrong->assertSessionMissing('errors');
        $this->assertNotNull(session($this->flagKey()), 'A wrong password must leave the CAPTCHA flag in place for the retry.');

        $right = $this->post(route('admin.login.store'), [
            'login' => self::LOGIN,
            'password' => self::PASSWORD,
        ]);

        $right->assertRedirect(route('admin.dashboard'));
        $right->assertSessionDoesntHaveErrors('captcha');
        $this->assertNull(session($this->flagKey()), 'A successful login clears the CAPTCHA flag.');
    }

    public function test_the_password_step_still_requires_a_token_without_the_flag(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => self::LOGIN,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_an_expired_flag_requires_a_token_again(): void
    {
        $this->withSession([$this->flagKey() => time() - 301]);

        $response = $this->post(route('admin.login.store'), [
            'login' => self::LOGIN,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    private function flagKey(): string
    {
        return 'captcha_verified_'.hash('sha256', self::LOGIN);
    }
}
