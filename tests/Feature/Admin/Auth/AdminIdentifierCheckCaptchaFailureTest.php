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

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\SecuritySetting;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * A CAPTCHA failure on the identifier step tells the client to reload the
 * page (so the widget issues a fresh token). The reason must survive that
 * reload as a flashed error, exactly like the validation branch does.
 */
class AdminIdentifierCheckCaptchaFailureTest extends TestCase
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

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secure-password-123'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        SecuritySetting::setValue('login_attempt_limit_enabled', false);
        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'turnstile');
        SecuritySetting::setValue('captcha_turnstile_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_turnstile_secret_key', 'test-secret-key');
        SecuritySetting::setValue('captcha_authentication_result', true);
        app(CaptchaService::class)->updateFormSetting('admin_login', true, 'turnstile');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_captcha_failure_flashes_the_reason_for_the_reload(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'admin@example.com',
            // No cf-turnstile-response: the driver rejects before calling Cloudflare.
        ]);

        $response->assertStatus(422)
            ->assertJson(['redirect' => true]);

        $message = $response->json('message');
        $this->assertNotEmpty($message);
        $this->assertSame($message, session('error'), 'the reason must be flashed so the reloaded page can show it');
        $this->assertNull(session('captcha_verified_'.hash('sha256', 'admin@example.com')));
    }
}
