<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the APP_URL install-seeding hardening: the host must be a
 * syntactically valid host (no .env injection), and the scheme must not be
 * inferred from a raw, untrusted forwarded header.
 */
class CheckInstallationReadyAppUrlSeedTest extends TestCase
{
    private function invokePrivate(object $obj, string $method, array $args): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($obj, $args);
    }

    public function test_is_valid_host_accepts_plausible_hosts(): void
    {
        $mw = new CheckInstallationReady();

        $this->assertTrue($this->invokePrivate($mw, 'isValidHost', ['example.com']));
        $this->assertTrue($this->invokePrivate($mw, 'isValidHost', ['example.com:8443']));
        $this->assertTrue($this->invokePrivate($mw, 'isValidHost', ['127.0.0.1:8080']));
        $this->assertTrue($this->invokePrivate($mw, 'isValidHost', ['[::1]:8080']));
    }

    public function test_is_valid_host_rejects_injection_and_malformed(): void
    {
        $mw = new CheckInstallationReady();

        $this->assertFalse($this->invokePrivate($mw, 'isValidHost', ["example.com\nAPP_DEBUG=true"]));
        $this->assertFalse($this->invokePrivate($mw, 'isValidHost', ['evil host']));
        $this->assertFalse($this->invokePrivate($mw, 'isValidHost', ['http://evil.com/']));
        $this->assertFalse($this->invokePrivate($mw, 'isValidHost', ['']));
    }

    public function test_detect_request_scheme_ignores_raw_forwarded_headers(): void
    {
        // Without a trusted proxy, a forged X-Forwarded-Proto / CF-Visitor must
        // NOT upgrade the detected scheme to https.
        $mw = new CheckInstallationReady();

        $request = Request::create('http://example.com/install', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->headers->set('CF-Visitor', '{"scheme":"https"}');

        $this->assertSame('http', $this->invokePrivate($mw, 'detectRequestScheme', [$request]));
    }
}
