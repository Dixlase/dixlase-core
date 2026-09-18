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

namespace Tests\Unit\Services\Captcha;

use App\Captcha\CaptchaDriver;
use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Helpers\CaptchaHelper;
use App\Models\SecuritySetting;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * reCAPTCHA Enterprise assesses a token against an expected action. The
 * driver used to send the literal 'admin_login' for every form, so tokens
 * from the inquiry form were assessed against the wrong action — and a token
 * obtained on one form could be replayed on another unnoticed, because the
 * mismatch was never checked.
 *
 * Google's assessment endpoint is faked; only the request the driver builds
 * and its reading of the response are under test.
 */
class GoogleRecaptchaEnterpriseExpectedActionTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://recaptchaenterprise.googleapis.com/v1/projects/test-project/assessments*';

    protected function setUp(): void
    {
        parent::setUp();

        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'google_enterprise');
        SecuritySetting::setValue('captcha_google_enterprise_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_google_enterprise_secret_key', 'test-api-key');
        SecuritySetting::setValue('captcha_google_enterprise_project_id', 'test-project');
        SecuritySetting::setValue('captcha_authentication_result', true);
        app(CaptchaService::class)->updateFormSetting('dixlase-inquiry.inquiry_contact', true, 'google_enterprise');
    }

    public function test_it_sends_the_form_action_in_google_form(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->assessment('dixlase_inquiry_inquiry_contact'))]);

        $result = $this->driver()
            ->withExpectedAction('dixlase-inquiry.inquiry_contact')
            ->verify($this->request());

        $this->assertTrue($result->isValid());
        Http::assertSent(fn ($request) => $request['event']['expectedAction'] === 'dixlase_inquiry_inquiry_contact');
    }

    public function test_it_rejects_a_token_issued_for_another_action(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->assessment('admin_login'))]);

        $result = $this->driver()
            ->withExpectedAction('dixlase-inquiry.inquiry_contact')
            ->verify($this->request());

        $this->assertFalse($result->isValid());
        $this->assertSame('action_mismatch', $result->metadata['invalid_reason'] ?? null);
    }

    public function test_it_sends_no_expected_action_when_none_was_given(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->assessment('whatever'))]);

        $result = $this->driver()->verify($this->request());

        $this->assertTrue($result->isValid(), 'Without an expected action there is nothing to compare against.');
        Http::assertSent(fn ($request) => ! array_key_exists('expectedAction', $request['event']));
    }

    public function test_with_expected_action_does_not_mutate_the_shared_driver(): void
    {
        $shared = $this->driver();
        $scoped = $shared->withExpectedAction('admin_login');

        $this->assertNotSame($shared, $scoped);

        Http::fake([self::ENDPOINT => Http::response($this->assessment('something_else'))]);
        $this->assertTrue($shared->verify($this->request())->isValid(), 'The shared instance still has no expected action.');
    }

    public function test_captcha_helper_hands_the_form_action_to_the_driver(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->assessment('dixlase_inquiry_inquiry_contact'))]);
        $this->app->instance(CaptchaDriver::class, $this->driver());

        $result = CaptchaHelper::verify($this->request(), 'dixlase-inquiry.inquiry_contact');

        $this->assertNotNull($result);
        $this->assertTrue($result->isValid());
        Http::assertSent(fn ($request) => $request['event']['expectedAction'] === 'dixlase_inquiry_inquiry_contact');
    }

    private function driver(): GoogleRecaptchaEnterpriseDriver
    {
        return new GoogleRecaptchaEnterpriseDriver([
            'site_key' => 'test-site-key',
            'api_key' => 'test-api-key',
            'project_id' => 'test-project',
            'min_score' => 0.5,
        ]);
    }

    private function request(): Request
    {
        return Request::create('/inquiry/embed/send', 'POST', ['g-recaptcha-response' => 'token-from-widget']);
    }

    /**
     * @return array<string, mixed>
     */
    private function assessment(string $action): array
    {
        return [
            'tokenProperties' => ['valid' => true, 'action' => $action],
            'riskAnalysis' => ['score' => 0.9],
        ];
    }
}
