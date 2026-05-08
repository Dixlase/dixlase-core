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
use App\Captcha\GoogleRecaptchaV2Driver;
use App\Captcha\GoogleRecaptchaV3Driver;
use App\Captcha\TurnstileCaptchaDriver;
use App\Models\SecuritySetting;
use App\Services\Captcha\CaptchaCspProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaCspProviderTest extends TestCase
{
    use RefreshDatabase;

    private CaptchaCspProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CaptchaCspProvider();
    }

    public function test_returns_empty_when_captcha_disabled(): void
    {
        SecuritySetting::set('captcha_enabled', '0');

        $this->assertSame([], $this->provider->getCspDirectives());
    }

    public function test_returns_turnstile_directives_when_turnstile_active(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->instance(CaptchaDriver::class, new TurnstileCaptchaDriver());

        $directives = $this->provider->getCspDirectives();

        $this->assertContains('https://challenges.cloudflare.com', $directives['script-src'] ?? []);
        $this->assertContains('https://challenges.cloudflare.com', $directives['frame-src'] ?? []);
        $this->assertContains('https://challenges.cloudflare.com', $directives['connect-src'] ?? []);
        $this->assertNotContains('https://www.google.com', $directives['script-src'] ?? []);
    }

    public function test_returns_recaptcha_directives_when_recaptcha_v3_active(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->instance(CaptchaDriver::class, new GoogleRecaptchaV3Driver());

        $directives = $this->provider->getCspDirectives();

        $this->assertContains('https://www.google.com', $directives['script-src'] ?? []);
        $this->assertContains('https://www.gstatic.com', $directives['script-src'] ?? []);
        $this->assertContains('https://www.google.com', $directives['frame-src'] ?? []);
        $this->assertContains('https://www.google.com', $directives['connect-src'] ?? []);
        $this->assertNotContains('https://challenges.cloudflare.com', $directives['script-src'] ?? []);
    }

    public function test_returns_recaptcha_directives_when_recaptcha_v2_active(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->instance(CaptchaDriver::class, new GoogleRecaptchaV2Driver());

        $directives = $this->provider->getCspDirectives();

        $this->assertContains('https://www.google.com', $directives['script-src'] ?? []);
        $this->assertContains('https://www.gstatic.com', $directives['script-src'] ?? []);
        $this->assertNotContains('https://challenges.cloudflare.com', $directives['script-src'] ?? []);
    }

    public function test_returns_empty_when_driver_resolution_fails(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->bind(CaptchaDriver::class, function () {
            throw new \RuntimeException('driver misconfigured');
        });

        $this->assertSame([], $this->provider->getCspDirectives());
    }
}
