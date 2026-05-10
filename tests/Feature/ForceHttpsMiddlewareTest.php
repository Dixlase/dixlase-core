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

namespace Tests\Feature;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Tests\TestCase;

class ForceHttpsMiddlewareTest extends TestCase
{
    protected ForceHttps $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ForceHttps();
    }

    public function test_http_request_redirects_to_https_when_force_ssl_enabled(): void
    {
        config(['app.force_ssl' => true]);

        $request = Request::create('http://example.com/admin/login', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(301, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://', $location);
        $this->assertStringEndsWith('/admin/login', $location);
    }

    public function test_http_request_not_redirected_when_force_ssl_disabled(): void
    {
        config(['app.force_ssl' => false]);

        $request = Request::create('http://example.com/admin/login', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_https_request_not_redirected_when_force_ssl_enabled(): void
    {
        config(['app.force_ssl' => true]);

        $request = Request::create('https://example.com/admin/login', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_request_with_forwarded_proto_https_not_redirected(): void
    {
        config(['app.force_ssl' => true]);

        $request = Request::create('http://example.com/admin/login', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_install_routes_excluded_from_redirect(): void
    {
        config(['app.force_ssl' => true]);

        $installPaths = ['install', 'install/settings', 'install/database'];

        foreach ($installPaths as $path) {
            $request = Request::create("http://example.com/{$path}", 'GET');

            $response = $this->middleware->handle($request, fn ($req) => response('OK'));

            $this->assertEquals(200, $response->getStatusCode(), "Path '{$path}' should be excluded from HTTPS redirect");
        }
    }

    public function test_csp_report_excluded_from_redirect(): void
    {
        config(['app.force_ssl' => true]);

        $request = Request::create('http://example.com/csp-report', 'POST');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ----------------------------------------------------------------
    // HSTS posture
    // ----------------------------------------------------------------

    public function test_hsts_header_omitted_by_default(): void
    {
        config([
            'app.force_ssl' => false,
            'security.hsts.max_age' => 0,
            'security.hsts.include_subdomains' => false,
            'security.hsts.preload' => false,
        ]);

        $request = Request::create('https://example.com/', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertFalse(
            $response->headers->has('Strict-Transport-Security'),
            'HSTS header must NOT be sent when max_age is 0 (default beta posture).',
        );
    }

    public function test_hsts_header_emitted_on_https_when_max_age_positive(): void
    {
        config([
            'security.hsts.max_age' => 300,
            'security.hsts.include_subdomains' => false,
            'security.hsts.preload' => false,
        ]);

        $request = Request::create('https://example.com/', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame(
            'max-age=300',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_hsts_header_includes_subdomains_directive_when_enabled(): void
    {
        config([
            'security.hsts.max_age' => 31536000,
            'security.hsts.include_subdomains' => true,
            'security.hsts.preload' => false,
        ]);

        $request = Request::create('https://example.com/', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_hsts_header_includes_preload_directive_when_enabled(): void
    {
        config([
            'security.hsts.max_age' => 31536000,
            'security.hsts.include_subdomains' => true,
            'security.hsts.preload' => true,
        ]);

        $request = Request::create('https://example.com/', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame(
            'max-age=31536000; includeSubDomains; preload',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_hsts_header_not_emitted_on_plain_http_response(): void
    {
        config([
            'app.force_ssl' => false,
            'security.hsts.max_age' => 31536000,
            'security.hsts.include_subdomains' => true,
            'security.hsts.preload' => false,
        ]);

        $request = Request::create('http://example.com/', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertFalse(
            $response->headers->has('Strict-Transport-Security'),
            'HSTS header must NOT be sent on plain-HTTP responses, even when configured.',
        );
    }

    public function test_hsts_header_emitted_when_request_is_https_via_forwarded_proto(): void
    {
        config([
            'security.hsts.max_age' => 86400,
            'security.hsts.include_subdomains' => false,
            'security.hsts.preload' => false,
        ]);

        $request = Request::create('http://example.com/', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame(
            'max-age=86400',
            $response->headers->get('Strict-Transport-Security'),
        );
    }
}
