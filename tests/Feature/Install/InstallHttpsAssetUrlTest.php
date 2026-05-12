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

declare(strict_types=1);

namespace Tests\Feature\Install;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the HTTPS asset-URL fix in AppServiceProvider::boot().
 *
 * Dixlase served behind a reverse proxy (Nginx → PHP-FPM) without
 * TRUSTED_PROXIES set was emitting http:// asset URLs during install, which
 * caused CSP 'self' violations because the page itself loads over https://.
 * The fix forces the URL generator to https when APP_URL declares https or
 * X-Forwarded-Proto says https, even before installation completes.
 */
class InstallHttpsAssetUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_page_uses_https_for_assets_when_app_url_is_https(): void
    {
        config(['app.url' => 'https://dixlase.org']);

        $response = $this->get('/install');

        $response->assertStatus(200);
        // The install layout should reference assets via https when APP_URL is https.
        $response->assertDontSee('http://dixlase.org/assets/', false);
    }

    public function test_install_page_uses_https_when_x_forwarded_proto_says_https(): void
    {
        config(['app.url' => 'http://localhost']);

        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'Host' => 'dixlase.org',
        ])->get('http://dixlase.org/install');

        $response->assertStatus(200);
        $response->assertDontSee('http://dixlase.org/assets/', false);
    }

    public function test_url_helper_emits_https_root_when_app_url_is_https(): void
    {
        config(['app.url' => 'https://dixlase.org']);

        // Hit a normal install route so AppServiceProvider::boot() runs against
        // a real request and applies URL::forceScheme/forceRootUrl.
        $this->get('/install');

        $this->assertStringStartsWith('https://', url('/install'));
        $this->assertStringStartsWith('https://', asset('assets/build/css/common_css.css'));
    }
}
