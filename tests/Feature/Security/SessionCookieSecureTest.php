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

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * Core #673: the session cookie used to fall back to an explicit
 * `secure = false`, so an HTTPS install that set neither
 * SESSION_SECURE_COOKIE nor FORCE_SSL sent it without the Secure flag.
 * Left unset, the flag now follows the request.
 */
class SessionCookieSecureTest extends TestCase
{
    use RefreshDatabase;

    private const PROBE = '/__session-cookie-probe';

    protected function setUp(): void
    {
        parent::setUp();

        // The probe is not part of the install flow; this middleware would
        // send it to the installer on a test database.
        $this->withoutMiddleware(CheckInstallationReady::class);

        Route::middleware('web')->get(self::PROBE, fn () => 'ok');
    }

    public function test_secure_is_left_to_the_request_when_neither_variable_is_set(): void
    {
        $this->assertNull($this->sessionConfigWith(['SESSION_SECURE_COOKIE' => null, 'FORCE_SSL' => null])['secure']);
        $this->assertNull($this->sessionConfigWith(['SESSION_SECURE_COOKIE' => null, 'FORCE_SSL' => 'false'])['secure']);
    }

    public function test_force_ssl_pins_secure_on(): void
    {
        $this->assertTrue($this->sessionConfigWith(['SESSION_SECURE_COOKIE' => null, 'FORCE_SSL' => 'true'])['secure']);
    }

    public function test_an_explicit_session_secure_cookie_wins(): void
    {
        $this->assertFalse($this->sessionConfigWith(['SESSION_SECURE_COOKIE' => 'false', 'FORCE_SSL' => 'true'])['secure']);
        $this->assertTrue($this->sessionConfigWith(['SESSION_SECURE_COOKIE' => 'true', 'FORCE_SSL' => 'false'])['secure']);
    }

    public function test_the_session_cookie_is_secure_on_https(): void
    {
        $this->assertNull(config('session.secure'));

        $response = $this->get('https://localhost'.self::PROBE);

        $this->assertTrue($this->sessionCookie($response)->isSecure());
        $this->assertTrue($this->sessionCookie($response, 'XSRF-TOKEN')->isSecure());
    }

    public function test_the_session_cookie_is_not_secure_on_plain_http(): void
    {
        $cookie = $this->sessionCookie($this->get('http://localhost'.self::PROBE));

        $this->assertFalse($cookie->isSecure());
    }

    public function test_the_session_cookie_is_secure_behind_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '127.0.0.1']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.7'])
            ->get('http://localhost'.self::PROBE);

        $this->assertTrue($this->sessionCookie($response)->isSecure());
    }

    public function test_a_forwarded_scheme_from_an_untrusted_client_is_ignored(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost'.self::PROBE);

        $this->assertFalse($this->sessionCookie($response)->isSecure());
    }

    /**
     * #631 item 1: IP allow-lists are database settings edited on the
     * security screen. None may return to the shipped configuration.
     */
    public function test_the_security_config_carries_no_ip_lists(): void
    {
        $walk = function (array $values, string $prefix) use (&$walk): void {
            foreach ($values as $key => $value) {
                $path = $prefix.$key;

                $this->assertDoesNotMatchRegularExpression('/_ips?$|allow_?list|white_?list|block_?list/i', (string) $key, "config({$path})");

                if (is_array($value)) {
                    $walk($value, $path.'.');
                } elseif (is_string($value)) {
                    $this->assertFalse(
                        $value === '*' || filter_var(explode('/', $value)[0], FILTER_VALIDATE_IP) !== false,
                        "config({$path}) holds an address: {$value}",
                    );
                }
            }
        };

        $walk(config('security'), 'security.');
    }

    private function sessionCookie(\Illuminate\Testing\TestResponse $response, ?string $name = null): Cookie
    {
        $response->assertOk();

        foreach ($response->baseResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === ($name ?? config('session.cookie'))) {
                return $cookie;
            }
        }

        $this->fail('The response did not set the cookie.');
    }

    /**
     * Evaluate config/session.php with the given environment variables
     * (null = absent) and restore them afterwards.
     *
     * @param  array<string, string|null>  $vars
     * @return array<string, mixed>
     */
    private function sessionConfigWith(array $vars): array
    {
        $saved = [];

        foreach ($vars as $name => $value) {
            $saved[$name] = [getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            $this->setEnv($name, $value);
        }

        try {
            return require base_path('config/session.php');
        } finally {
            foreach ($saved as $name => [$putenv, $env, $server]) {
                $this->setEnv($name, $putenv === false ? null : $putenv);
                if ($env !== null) {
                    $_ENV[$name] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$name] = $server;
                }
            }
        }
    }

    private function setEnv(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
}
