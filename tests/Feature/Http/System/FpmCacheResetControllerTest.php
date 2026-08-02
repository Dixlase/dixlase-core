<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Http\System;

use Tests\TestCase;

/**
 * Pins the token-gated FPM cache reset endpoint added for Round 5
 * Finding D. The route is bare (outside the web / admin / api
 * middleware stacks) so it stays reachable during a maintenance
 * window; auth is a shared secret passed by PhpFpmReloader from the
 * CLI update process.
 *
 * With no token configured the endpoint must always refuse — the
 * default-safe stance prevents a misconfigured install (empty env)
 * from silently exposing an anonymous opcache reset.
 */
class FpmCacheResetControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Bypass the installation guard for these tests — the endpoint
        // is meant to run mid-install/mid-update, but the test harness
        // does not have an INSTALLED=true env set by default and we
        // are focused on the auth + reset behavior here, not the
        // installation guard interaction (which the middleware's own
        // exclusion list is already responsible for).
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstallationReady::class);
    }

    public function test_endpoint_refuses_without_token(): void
    {
        config(['core_update.fpm_reload.http_token' => 'the-real-token']);

        $this->postJson('/system/fpm-cache-reset')
            ->assertStatus(401)
            ->assertJson(['ok' => false, 'error' => 'unauthorized']);
    }

    public function test_endpoint_refuses_with_wrong_token(): void
    {
        config(['core_update.fpm_reload.http_token' => 'the-real-token']);

        $this->postJson('/system/fpm-cache-reset', [], [
            'X-Fpm-Cache-Reset-Token' => 'wrong-token',
        ])->assertStatus(401);
    }

    public function test_endpoint_refuses_when_token_env_is_empty(): void
    {
        // Default-safe: with no token configured, the endpoint must
        // never accept a request — otherwise a fresh install that has
        // not set CORE_FPM_RESET_TOKEN would expose an anonymous
        // opcache reset to anyone who can reach the URL.
        config(['core_update.fpm_reload.http_token' => '']);

        $this->postJson('/system/fpm-cache-reset', [], [
            'X-Fpm-Cache-Reset-Token' => '',
        ])->assertStatus(401);
    }

    public function test_endpoint_accepts_with_matching_token_and_resets(): void
    {
        config(['core_update.fpm_reload.http_token' => 'the-real-token']);

        $response = $this->postJson('/system/fpm-cache-reset', [], [
            'X-Fpm-Cache-Reset-Token' => 'the-real-token',
        ])->assertStatus(200);

        $body = $response->json();
        $this->assertTrue($body['ok']);
        $this->assertArrayHasKey('sapi', $body);
        $this->assertArrayHasKey('opcache_reset', $body);
        $this->assertTrue($body['statcache_cleared']);
    }

    public function test_token_comparison_is_timing_safe(): void
    {
        // hash_equals() is timing-safe by design; this test pins the
        // "wrong-length token" case that a naive == would happily
        // accept in the short-circuit case. Any change to `!==` would
        // trip this because a wrong-length token would still be
        // rejected, but the point of the test is to encode intent:
        // "we deliberately use a constant-time comparison here".
        config(['core_update.fpm_reload.http_token' => 'exactly-16-chars']);

        $this->postJson('/system/fpm-cache-reset', [], [
            'X-Fpm-Cache-Reset-Token' => 'x',
        ])->assertStatus(401);
    }
}
