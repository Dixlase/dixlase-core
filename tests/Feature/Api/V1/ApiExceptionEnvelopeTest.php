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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiExceptionEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // CheckInstallationReady redirects to /install/* when INSTALLED is
        // not "true"; required for any non-install route under test.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        // Pin app.force_ssl to false so the ForceHttps middleware does
        // not 301 to HTTPS during plain-HTTP test requests.
        config()->set('app.force_ssl', false);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_unmatched_api_route_returns_404_in_unified_envelope(): void
    {
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertNotFound();
        $response->assertJsonStructure([
            'error' => ['code', 'message'],
            'meta' => ['site_id', 'timestamp'],
        ]);
        $response->assertJsonPath('error.code', 'not_found');
        $response->assertJsonPath('meta.site_id', 1);
    }

    public function test_unmatched_api_route_does_not_redirect_to_locale_prefix(): void
    {
        // Guards against a future regression where a multilingual plugin's
        // Route::fallback() catches /api/* and 302-redirects to
        // /{locale}/api/... — the API surface must stay opaque to locale
        // routing per docs/development/api-reference/versioning.md §1.2.

        $response = $this->getJson('/api/v1/typo');

        $this->assertNotEquals(301, $response->getStatusCode(), 'API URLs must not 301-redirect');
        $this->assertNotEquals(302, $response->getStatusCode(), 'API URLs must not 302-redirect');
        $response->assertNotFound();
    }

    public function test_wrong_http_method_returns_405_in_unified_envelope(): void
    {
        $response = $this->postJson('/api/v1/health');

        $this->assertSame(405, $response->getStatusCode());
        $response->assertJsonPath('error.code', 'method_not_allowed');
        $response->assertJsonStructure([
            'error' => ['code', 'message'],
            'meta' => ['site_id', 'timestamp'],
        ]);
    }

    public function test_non_api_404_keeps_default_html_behavior(): void
    {
        // The exception renderers must NOT swallow web 404s into JSON.
        // Web 404s remain Laravel's default HTML response so the admin
        // UI and front-end keep working.

        $response = $this->get('/this-is-not-an-api-route');

        $response->assertNotFound();
        $this->assertStringNotContainsString('"error"', $response->getContent() ?: '', 'Web 404 must not be JSON');
    }
}
