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

namespace Tests\Feature\Captcha;

use App\Helpers\CaptchaHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\SecuritySetting;
use App\Services\CaptchaService;
use App\Traits\VerifiesCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Probe request shaped like the plugin form requests: it merges the driver's
 * token rule into rules() and relies on the trait for the provider check.
 */
class VerifiesCaptchaProbeRequest extends FormRequest
{
    use VerifiesCaptcha;

    protected ?string $captchaFormKey = 'admin_login';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['name' => 'required|string'];

        if (CaptchaHelper::shouldShowCaptcha('admin_login')) {
            $rules = array_merge($rules, $this->getCaptchaRules());
        }

        return $rules;
    }
}

/**
 * Regression: a request that only merged getCaptchaRules() accepted any
 * non-empty token because nothing ever called the provider. The trait now
 * verifies the token after the rules pass.
 */
class VerifiesCaptchaTraitTest extends TestCase
{
    use RefreshDatabase;

    private const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        Route::post('/_captcha-probe', fn (VerifiesCaptchaProbeRequest $request) => response()->json(['ok' => true]));

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

    public function test_a_token_the_provider_rejects_fails_validation(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => false, 'error-codes' => ['invalid-input-secret']])]);

        $response = $this->postJson('/_captcha-probe', ['name' => 'x', 'cf-turnstile-response' => 'any-string']);

        // bootstrap/app.php renders validation failures as error.details.{field}
        $response->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->assertSame(
            __('admin/settings/security/captcha.turnstile_errors.invalid-input-secret'),
            $response->json('error.details.cf-turnstile-response.0'),
        );
        Http::assertSentCount(1);
    }

    public function test_a_token_the_provider_accepts_passes(): void
    {
        Http::fake([self::SITEVERIFY => Http::response(['success' => true])]);

        $this->postJson('/_captcha-probe', ['name' => 'x', 'cf-turnstile-response' => 'good'])
            ->assertOk()->assertJson(['ok' => true]);

        Http::assertSentCount(1);
    }

    public function test_a_missing_token_is_reported_without_calling_the_provider(): void
    {
        Http::fake();

        $this->postJson('/_captcha-probe', ['name' => 'x'])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['cf-turnstile-response']]]);

        Http::assertNothingSent();
    }

    public function test_verification_is_skipped_when_captcha_is_off_for_the_form(): void
    {
        Http::fake();
        app(CaptchaService::class)->updateFormSetting('admin_login', false, 'turnstile');

        $this->postJson('/_captcha-probe', ['name' => 'x'])->assertOk();

        Http::assertNothingSent();
    }
}
