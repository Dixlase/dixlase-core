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

namespace Tests\Feature\Admin\Settings\Security;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The per-form toggles on the CAPTCHA settings page carry a note saying what
 * the CAPTCHA covers. The admin login note has to say that passkey sign-in
 * is not gated — the toggle's name reads as "the login page", and an operator
 * who tests a broken key with a passkey would otherwise take it for a bug.
 */
class CaptchaFormDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);

        SecuritySetting::setValue('two_fa_mode', 0);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_admin_login_toggle_explains_that_passkeys_are_not_gated(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.security.captcha'));

        $response->assertOk()
            ->assertSee(__('admin/settings/security/captcha.form_descriptions.admin_login'))
            ->assertSee(__('admin/settings/security/captcha.form_descriptions.admin_two_fa'));
    }

    public function test_both_locales_carry_the_passkey_note(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $note = trans('admin/settings/security/captcha.form_descriptions.admin_login', [], $locale);

            $this->assertNotSame('admin/settings/security/captcha.form_descriptions.admin_login', $note, "{$locale} note missing");
            $this->assertMatchesRegularExpression('/passkey|パスキー/i', $note);
        }
    }
}
