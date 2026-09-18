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

use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Captcha\GoogleRecaptchaV3Driver;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the action name the Google drivers hand to grecaptcha.execute().
 *
 * Google accepts only A-Za-z/_ there. When the name contains anything else it
 * rejects the call with "Invalid action name, may only include A-Za-z/_", the
 * hidden g-recaptcha-response field stays empty, and the server then refuses a
 * legitimate submission with "reCAPTCHA response is required". That is exactly
 * what happened on dixlase.org: the inquiry form's key is
 * `dixlase-inquiry.inquiry_contact`, so every submission failed while admin
 * login — whose action contains only legal characters — worked.
 *
 * The widget markup is rendered from config alone, so these assertions need no
 * network and no provider keys.
 */
class GoogleRecaptchaActionNameTest extends TestCase
{
    // The driver constructors read the stored provider settings, so they need
    // the settings tables even though these assertions only touch markup.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // renderWidget() returns an empty string unless the driver is the
        // active one, so switch the stored settings to Google before asserting
        // on the markup. Enterprise reads the same driver key and its own
        // version is not consulted.
        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'google');
        SecuritySetting::setValue('captcha_google_version', 'v3');
        SecuritySetting::setValue('captcha_google_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_google_secret_key', 'test-secret-key');
        SecuritySetting::setValue('captcha_authentication_result', true);
    }

    public function test_v3_widget_translates_an_illegal_action_name(): void
    {
        $html = $this->v3Driver()->renderWidget(['action' => 'dixlase-inquiry.inquiry_contact']);

        $this->assertStringContainsString("{action: 'dixlase_inquiry_inquiry_contact'}", $html);
        $this->assertStringNotContainsString('dixlase-inquiry.inquiry_contact', $html);
    }

    public function test_v3_widget_leaves_a_legal_action_name_alone(): void
    {
        $html = $this->v3Driver()->renderWidget(['action' => 'admin_login']);

        $this->assertStringContainsString("{action: 'admin_login'}", $html);
    }

    public function test_v3_widget_falls_back_to_submit_when_nothing_legal_remains(): void
    {
        $html = $this->v3Driver()->renderWidget(['action' => '---']);

        $this->assertStringContainsString("{action: 'submit'}", $html);
    }

    public function test_enterprise_widget_translates_an_illegal_action_name(): void
    {
        // Enterprise is a separate provider: it only renders when it is the
        // selected driver and its own keys are stored.
        SecuritySetting::setValue('captcha_driver', 'google_enterprise');
        SecuritySetting::setValue('captcha_google_enterprise_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_google_enterprise_secret_key', 'test-secret-key');

        $html = $this->enterpriseDriver()->renderWidget(['action' => 'dixlase-inquiry.inquiry_contact']);

        $this->assertStringContainsString("{action: 'dixlase_inquiry_inquiry_contact'}", $html);
    }

    public function test_v3_widget_does_not_log_the_site_key_to_the_console(): void
    {
        // Debug logging shipped to production and printed the site key and a
        // token prefix on every page load; only the failure paths stay.
        $html = $this->v3Driver()->renderWidget(['action' => 'admin_login']);

        $this->assertStringNotContainsString('console.log', $html);
    }

    private function v3Driver(): GoogleRecaptchaV3Driver
    {
        return new GoogleRecaptchaV3Driver([
            'site_key' => 'test-site-key',
            'secret_key' => 'test-secret-key',
            'min_score' => 0.5,
        ]);
    }

    private function enterpriseDriver(): GoogleRecaptchaEnterpriseDriver
    {
        return new GoogleRecaptchaEnterpriseDriver([
            'site_key' => 'test-site-key',
            'secret_key' => 'test-secret-key',
            'project_id' => 'test-project',
            'min_score' => 0.5,
        ]);
    }
}
