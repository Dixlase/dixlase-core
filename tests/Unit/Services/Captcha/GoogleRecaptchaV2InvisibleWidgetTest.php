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

use App\Captcha\GoogleRecaptchaV2Driver;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the reCAPTCHA v2 invisible widget.
 *
 * The invisible variant shipped broken on any site with CSP enabled: its
 * inline script carried no nonce, so the browser blocked it, no token was ever
 * written, and every submission came back as "reCAPTCHA response is required".
 * On dixlase.org that locked the admin out of the login form. Two further
 * faults were in the same block: it bound to `document.querySelector('form')`
 * — the first form on the page rather than its own — and it used a fixed
 * element id, so a second form on the page broke both widgets.
 *
 * It also submitted the form from the challenge callback via
 * HTMLFormElement.prototype.submit(), which cannot work for a form that posts
 * with fetch(); the token is now fetched on load and written into the hidden
 * field the way v3 does, so the page submits however it likes.
 */
class GoogleRecaptchaV2InvisibleWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SecuritySetting::setValue('captcha_enabled', true);
        SecuritySetting::setValue('captcha_driver', 'google');
        SecuritySetting::setValue('captcha_google_version', 'v2_invisible');
        SecuritySetting::setValue('captcha_google_site_key', 'test-site-key');
        SecuritySetting::setValue('captcha_google_secret_key', 'test-secret-key');
        SecuritySetting::setValue('captcha_authentication_result', true);
    }

    public function test_it_carries_a_csp_nonce_on_the_inline_script(): void
    {
        $html = $this->render();

        // csp_nonce() returns a value in the test app; the assertion is that
        // the inline <script> is not emitted bare.
        $this->assertStringNotContainsString("<script>\n", $html);
        $this->assertMatchesRegularExpression('/<script nonce="[^"]+">/', $html);
    }

    public function test_it_ships_the_hidden_token_field(): void
    {
        $this->assertStringContainsString('name="g-recaptcha-response"', $this->render());
    }

    public function test_it_binds_to_its_own_form_rather_than_the_first_one_on_the_page(): void
    {
        $html = $this->render();

        $this->assertStringContainsString("closest('form')", $html);
        $this->assertStringNotContainsString("document.querySelector('form')", $html);
    }

    public function test_it_does_not_submit_the_form_from_the_challenge_callback(): void
    {
        // A fetch()-posted form is already in flight by then.
        $this->assertStringNotContainsString('HTMLFormElement.prototype.submit', $this->render());
    }

    public function test_two_widgets_on_one_page_get_distinct_element_ids(): void
    {
        preg_match('/recaptcha-container-(\w+)/', $this->render(), $first);
        preg_match('/recaptcha-container-(\w+)/', $this->render(), $second);

        $this->assertNotSame($first[1], $second[1]);
    }

    public function test_checkbox_variant_is_unchanged(): void
    {
        SecuritySetting::setValue('captcha_google_version', 'v2_checkbox');

        $html = $this->render();

        $this->assertStringContainsString('class="g-recaptcha"', $html);
        $this->assertStringNotContainsString('size\': \'invisible', $html);
    }

    private function render(): string
    {
        return (new GoogleRecaptchaV2Driver())->renderWidget(['action' => 'admin_login']);
    }
}
