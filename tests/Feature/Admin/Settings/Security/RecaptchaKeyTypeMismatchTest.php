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

use App\Captcha\GoogleRecaptchaV3Driver;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The three Google reCAPTCHA versions share one site key / secret pair in
 * the settings, so a v2 pair left in place after switching to v3 is easy to
 * do. siteverify distinguishes them (a v3 key always returns a score), and
 * both the admin widget test and the live v3 driver must say so instead of
 * passing the test and then rejecting every real submission as score 0.
 */
class RecaptchaKeyTypeMismatchTest extends TestCase
{
    use RefreshDatabase;

    private const SITEVERIFY = 'https://www.google.com/recaptcha/api/siteverify';

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

    private function validate(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->superAdmin, 'member')->postJson(route('admin.settings.security.captcha.validate-widget'), array_merge([
            'token' => 'widget-token',
            'driver' => 'google',
            'site_key' => 'site',
            'secret_key' => 'secret',
            'version' => 'v3',
            'min_score' => '0.5',
        ], $overrides));
    }

    public function test_widget_test_rejects_a_v2_key_pair_configured_as_v3(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]); // v2: no score

        $this->validate()->assertOk()->assertJson([
            'success' => false,
            'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v2_to_v3'),
        ]);
    }

    public function test_widget_test_rejects_a_v3_key_pair_configured_as_v2(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true, 'score' => 0.9, 'action' => 'test'])]);

        $this->validate(['version' => 'v2_invisible'])->assertOk()->assertJson([
            'success' => false,
            'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v3_to_v2'),
        ]);
    }

    public function test_widget_test_accepts_a_matching_v3_pair(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true, 'score' => 0.9, 'action' => 'test'])]);

        $this->validate()->assertOk()->assertJson(['success' => true, 'score' => 0.9]);
    }

    public function test_v3_driver_names_the_key_type_instead_of_a_zero_score(): void
    {
        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'google');
        SecuritySetting::setValue('captcha_google_version', 'v3');
        SecuritySetting::setValue('captcha_google_site_key', 'site');
        SecuritySetting::setValue('captcha_google_secret_key', 'secret');
        SecuritySetting::setValue('captcha_authentication_result', true);
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);

        $result = (new GoogleRecaptchaV3Driver())->verify(Request::create('/inquiry', 'POST', ['g-recaptcha-response' => 'tok']));

        $this->assertFalse($result->isValid());
        $this->assertSame('key_type_mismatch', $result->metadata['invalid_reason'] ?? null);
        $this->assertSame(
            __('admin/settings/security/captcha.test_key_version_mismatch_v2_to_v3'),
            $result->getErrorMessage(),
        );
    }
}
