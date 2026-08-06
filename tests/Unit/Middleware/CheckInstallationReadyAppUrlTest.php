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

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Targets the APP_URL seeding logic added to CheckInstallationReady.
 *
 * Scheme detection has to work even when the application is behind a reverse
 * proxy whose IP is not yet listed in TRUSTED_PROXIES — which is the typical
 * situation during the very first installer hit, before .env exists.
 */
class CheckInstallationReadyAppUrlTest extends TestCase
{
    private CheckInstallationReady $middleware;

    private ReflectionMethod $detectScheme;

    private ReflectionMethod $seedAppUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckInstallationReady();
        $this->detectScheme = new ReflectionMethod($this->middleware, 'detectRequestScheme');
        $this->detectScheme->setAccessible(true);
        $this->seedAppUrl = new ReflectionMethod($this->middleware, 'seedAppUrl');
        $this->seedAppUrl->setAccessible(true);
    }

    public function test_detect_returns_https_for_a_secure_request(): void
    {
        $request = Request::create('https://dixlase.org/install', 'GET');

        $this->assertSame('https', $this->detectScheme->invoke($this->middleware, $request));
    }

    public function test_detect_ignores_untrusted_x_forwarded_proto(): void
    {
        // Without a trusted proxy, a raw X-Forwarded-Proto must NOT be believed,
        // otherwise a client could forge the seeded APP_URL scheme.
        $request = Request::create('http://dixlase.org/install', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $this->assertSame('http', $this->detectScheme->invoke($this->middleware, $request));
    }

    public function test_detect_honours_forwarded_proto_from_trusted_proxy(): void
    {
        Request::setTrustedProxies(
            ['127.0.0.1'],
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

        try {
            $request = Request::create('http://dixlase.org/install', 'GET');
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            $request->headers->set('X-Forwarded-For', '203.0.113.9');
            $request->headers->set('X-Forwarded-Proto', 'https');

            $this->assertSame('https', $this->detectScheme->invoke($this->middleware, $request));
        } finally {
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        }
    }

    public function test_detect_ignores_comma_separated_untrusted_forwarded_proto(): void
    {
        // The raw multi-value parsing is gone; without a trusted proxy the
        // header is ignored entirely.
        $request = Request::create('http://dixlase.org/install', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https, http');

        $this->assertSame('http', $this->detectScheme->invoke($this->middleware, $request));
    }

    public function test_detect_ignores_cloudflare_cf_visitor_header(): void
    {
        // CF-Visitor is a raw, unauthenticated header and is no longer trusted.
        $request = Request::create('http://dixlase.org/install', 'GET');
        $request->headers->set('CF-Visitor', json_encode(['scheme' => 'https']));

        $this->assertSame('http', $this->detectScheme->invoke($this->middleware, $request));
    }

    public function test_detect_falls_back_to_plain_http_when_no_forwarded_headers(): void
    {
        $request = Request::create('http://dixlase.org/install', 'GET');

        $this->assertSame('http', $this->detectScheme->invoke($this->middleware, $request));
    }

    public function test_seed_overwrites_placeholder_app_url_in_env(): void
    {
        $envPath = $this->makeTempEnv("APP_NAME=Dixlase\nAPP_URL=http://localhost\nAPP_ENV=local\n");
        $request = Request::create('https://dixlase.org/install', 'GET');

        $this->seedAppUrl->invoke($this->middleware, $request, $envPath);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString("\nAPP_URL=https://dixlase.org\n", "\n".$contents);
        $this->assertStringNotContainsString('APP_URL=http://localhost', $contents);

        @unlink($envPath);
    }

    public function test_seed_appends_app_url_when_line_is_missing(): void
    {
        $envPath = $this->makeTempEnv("APP_NAME=Dixlase\nAPP_ENV=local\n");
        $request = Request::create('https://dixlase.org/install', 'GET');

        $this->seedAppUrl->invoke($this->middleware, $request, $envPath);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('APP_URL=https://dixlase.org', $contents);

        @unlink($envPath);
    }

    public function test_seed_ignores_untrusted_proxy_scheme(): void
    {
        $envPath = $this->makeTempEnv("APP_URL=http://localhost\n");
        // A raw X-Forwarded-Proto from an untrusted client must not upgrade the
        // seeded scheme — it stays http.
        $request = Request::create('http://dixlase.org/install', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $this->seedAppUrl->invoke($this->middleware, $request, $envPath);

        $this->assertStringContainsString('APP_URL=http://dixlase.org', file_get_contents($envPath));

        @unlink($envPath);
    }

    private function makeTempEnv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dixlase-env-');
        file_put_contents($path, $contents);

        return $path;
    }
}
