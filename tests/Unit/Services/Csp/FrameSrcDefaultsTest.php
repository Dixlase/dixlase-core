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

namespace Tests\Unit\Services\Csp;

use App\Captcha\CaptchaDriver;
use App\Captcha\TurnstileCaptchaDriver;
use App\Models\SecuritySetting;
use App\Services\Csp\CspBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FrameSrcDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private CspBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(CspBuilder::class);
    }

    public function test_front_default_blocks_all_frames(): void
    {
        $this->builder->setContext('front');
        $this->mockRequestPath('/');

        $csp = $this->builder->build();

        $this->assertMatchesRegularExpression(
            "/frame-src\\s+'none'(\\s|;|$)/",
            $csp,
            'Front frame-src should default to \'none\' so the page cannot embed iframes.'
        );
    }

    public function test_admin_relaxes_frame_src_to_self(): void
    {
        $this->builder->setContext('admin');
        $this->mockRequestPath('admin/dashboard');

        $csp = $this->builder->build();

        $this->assertMatchesRegularExpression(
            "/frame-src\\s+'self'(\\s|;|$)/",
            $csp,
            'Admin frame-src should be \'self\' to allow same-origin preview iframes.'
        );
    }

    public function test_front_with_captcha_only_allows_captcha_origin(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->instance(CaptchaDriver::class, new TurnstileCaptchaDriver());

        $this->builder->setContext('front');
        $this->mockRequestPath('/');

        $csp = $this->builder->build();

        $this->assertStringContainsString('https://challenges.cloudflare.com', $csp);
        $this->assertDoesNotMatchRegularExpression(
            "/frame-src\\s[^;]*'self'/",
            $csp,
            'Front frame-src should NOT include \'self\' even when captcha adds an origin.'
        );
        $this->assertDoesNotMatchRegularExpression(
            "/frame-src\\s+'none'/",
            $csp,
            'Captcha origin should override the \'none\' default.'
        );
    }

    public function test_admin_with_captcha_keeps_self_and_adds_captcha_origin(): void
    {
        SecuritySetting::set('captcha_enabled', '1');
        $this->app->instance(CaptchaDriver::class, new TurnstileCaptchaDriver());

        $this->builder->setContext('admin');
        $this->mockRequestPath('admin/login');

        $csp = $this->builder->build();

        $this->assertMatchesRegularExpression(
            "/frame-src\\s[^;]*'self'/",
            $csp,
            'Admin frame-src should retain \'self\' alongside captcha origin.'
        );
        $this->assertStringContainsString('https://challenges.cloudflare.com', $csp);
    }

    private function mockRequestPath(string $path): void
    {
        $request = Request::create('/'.ltrim($path, '/'));
        $this->app->instance('request', $request);
    }
}
