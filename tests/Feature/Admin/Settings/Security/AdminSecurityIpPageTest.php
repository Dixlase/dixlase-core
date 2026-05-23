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

namespace Tests\Feature\Admin\Settings\Security;

use App\Http\Middleware\CheckInstallationReady;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityIpPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            \App\Http\Middleware\EnsureEmailIsVerified::class,
            \App\Http\Middleware\CheckMenuAccess::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        \Illuminate\Support\Facades\View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    /**
     * The IP settings page renders and shows the detected client IP (feature #1).
     */
    public function test_ip_settings_page_shows_detected_client_ip(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($member)->get(route('admin.settings.security.ip'));

        $response->assertStatus(200);
        $response->assertSee(__('admin/settings/security/ip.detected_ip_label'));
    }

    /**
     * The page warns when a forwarded header arrives but TRUSTED_PROXIES is unset (feature #2).
     */
    public function test_ip_settings_page_warns_when_behind_untrusted_proxy(): void
    {
        config(['trustedproxy.proxies' => []]);

        $member = Member::factory()->create();

        $response = $this->actingAs($member)
            ->withHeaders(['X-Forwarded-For' => '203.0.113.1'])
            ->get(route('admin.settings.security.ip'));

        $response->assertStatus(200);
        $response->assertSee(__('admin/settings/security/ip.proxy_warning_heading'));
    }
}
