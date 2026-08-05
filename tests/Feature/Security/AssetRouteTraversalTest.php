<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the static asset delivery route (routes/front.php).
 *
 * The route serves theme/admin/plugin assets and is intentionally
 * unauthenticated (session/CSRF middleware are stripped). A path-traversal
 * flaw here is a pre-authentication arbitrary file read (e.g. .env, logs).
 * These tests lock in the traversal guard.
 */
class AssetRouteTraversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_encoded_traversal_to_env_is_blocked_for_theme_assets(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        // %2F keeps the "../" out of the web-server path normaliser so it
        // reaches the route parameter as literal "../../../.env".
        $response = $this->get('/assets/theme/..%2F..%2F..%2F.env');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
    }

    public function test_encoded_traversal_to_env_is_blocked_for_admin_assets(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        $response = $this->get('/assets/admin/..%2F..%2F..%2F.env');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
    }

    public function test_plugin_type_cannot_traverse_via_the_file_segment(): void
    {
        // For type=plugin the same segment feeds both the plugin directory and
        // the file name, so it must reject traversal before either is built.
        $this->withoutMiddleware([CheckInstallationReady::class]);

        $response = $this->get('/assets/plugin/..%2F..%2F..%2F.env');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
    }

    public function test_null_byte_in_asset_path_is_rejected(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        $response = $this->get('/assets/theme/app.css%00.env');

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
