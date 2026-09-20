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

namespace Tests\Feature\Api\V1;

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // CheckInstallationReady redirects to /install/* when INSTALLED is
        // not set to "true"; the API surface needs the app to be in the
        // installed state to reach the route layer.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        // Tests run over plain HTTP; ForceHttps reads config('app.force_ssl')
        // and 301-redirects to HTTPS when true. Pin it false so routing /
        // exception logic is exercised directly.
        config()->set('app.force_ssl', false);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_health_endpoint_returns_unified_envelope_with_status_and_version(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['status', 'version'],
            'meta' => ['site_id', 'timestamp'],
        ]);
        $response->assertJsonPath('data.status', 'ok');
        $response->assertJsonPath('data.version', HealthController::VERSION);
        $response->assertJsonPath('meta.site_id', 1);
    }

    public function test_health_endpoint_is_unauthenticated(): void
    {
        // No Authorization header — must still succeed.
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
    }

    public function test_health_route_has_named_route_in_versioned_namespace(): void
    {
        $url = route('api.v1.health');

        $this->assertStringEndsWith('/api/v1/health', $url);
    }
}
