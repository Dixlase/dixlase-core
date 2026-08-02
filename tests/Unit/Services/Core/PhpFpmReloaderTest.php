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

namespace Tests\Unit\Services\Core;

use App\Services\Core\PhpFpmReloader;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the Round 5 PR-N behavior of the FPM cache reloader: both
 * paths (HTTP self-request + posix_kill signal) are best-effort, both
 * run independently, and a failure in either never aborts the
 * calling core operation. The `reload()` return array documents which
 * paths were attempted and their outcomes so the caller (CoreUpdater
 * / CoreRollback) can log a post-mortem.
 */
class PhpFpmReloaderTest extends TestCase
{
    public function test_disabled_flag_skips_both_paths(): void
    {
        config([
            'core_update.fpm_reload.enabled' => false,
            'core_update.fpm_reload.http_url' => 'http://example.test/system/fpm-cache-reset',
            'core_update.fpm_reload.http_token' => 'x',
            'core_update.fpm_reload.signal_pid' => 1,
        ]);
        Http::fake();

        $result = (new PhpFpmReloader())->reload();

        $this->assertFalse($result['enabled']);
        $this->assertNull($result['http']);
        $this->assertNull($result['signal']);
        Http::assertNothingSent();
    }

    public function test_http_skipped_when_url_missing(): void
    {
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => '',
            'core_update.fpm_reload.http_token' => 'x',
            'core_update.fpm_reload.signal_pid' => 0, // disable signal too, isolate the HTTP branch
        ]);
        Http::fake();

        $result = (new PhpFpmReloader())->reload();

        $this->assertFalse($result['http']['attempted']);
        $this->assertSame('http_url not configured', $result['http']['error']);
        Http::assertNothingSent();
    }

    public function test_http_skipped_when_token_missing(): void
    {
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => 'http://example.test/system/fpm-cache-reset',
            'core_update.fpm_reload.http_token' => '',
            'core_update.fpm_reload.signal_pid' => 0,
        ]);
        Http::fake();

        $result = (new PhpFpmReloader())->reload();

        $this->assertFalse($result['http']['attempted']);
        $this->assertSame('http_token not configured', $result['http']['error']);
        Http::assertNothingSent();
    }

    public function test_http_sends_token_header_and_reports_success(): void
    {
        $url = 'http://example.test/system/fpm-cache-reset';
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => $url,
            'core_update.fpm_reload.http_token' => 'the-real-token',
            'core_update.fpm_reload.signal_pid' => 0,
        ]);
        Http::fake([
            $url => Http::response(['ok' => true], 200),
        ]);

        $result = (new PhpFpmReloader())->reload();

        $this->assertTrue($result['http']['attempted']);
        $this->assertTrue($result['http']['ok']);
        $this->assertSame(200, $result['http']['status']);
        $this->assertNull($result['http']['error']);

        Http::assertSent(function (HttpRequest $request) use ($url) {
            return $request->method() === 'POST'
                && $request->url() === $url
                && $request->hasHeader('X-Fpm-Cache-Reset-Token', 'the-real-token');
        });
    }

    public function test_http_reports_non_success_status_as_failure(): void
    {
        $url = 'http://example.test/system/fpm-cache-reset';
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => $url,
            'core_update.fpm_reload.http_token' => 'the-real-token',
            'core_update.fpm_reload.signal_pid' => 0,
        ]);
        Http::fake([
            $url => Http::response(['ok' => false], 401),
        ]);

        $result = (new PhpFpmReloader())->reload();

        $this->assertTrue($result['http']['attempted']);
        $this->assertFalse($result['http']['ok']);
        $this->assertSame(401, $result['http']['status']);
        $this->assertStringContainsString('401', (string) $result['http']['error']);
    }

    public function test_signal_skipped_when_pid_not_configured(): void
    {
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => '',
            'core_update.fpm_reload.http_token' => '',
            'core_update.fpm_reload.signal_pid' => 0,
        ]);
        Http::fake();

        $result = (new PhpFpmReloader())->reload();

        $this->assertFalse($result['signal']['attempted']);
        $this->assertSame('signal_pid not configured (<= 0)', $result['signal']['error']);
    }

    public function test_signal_reports_permission_denied_gracefully(): void
    {
        // A www-data-owned test worker cannot signal PID 1 (root). The
        // reload() call must NOT throw — the failure is expected and
        // simply reported in the result. This is the exact scenario
        // that plays out when the sandbox admin UI triggers an update:
        // the FPM-spawned CLI runs as www-data and cannot USR2 the
        // fpm master.
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => '',
            'core_update.fpm_reload.http_token' => '',
            'core_update.fpm_reload.signal_pid' => 1,
        ]);
        Http::fake();

        // Guard: this test only exercises the failure branch when
        // posix_kill exists AND the current process cannot signal
        // PID 1. In CI both are true (PHPUnit runs as a non-root
        // user); in a rare root-run environment posix_kill would
        // succeed and this assertion path would not apply, so we
        // just verify the shape either way.
        if (! function_exists('posix_kill')) {
            $result = (new PhpFpmReloader())->reload();
            $this->assertFalse($result['signal']['attempted']);
            $this->assertSame('posix_kill() not available', $result['signal']['error']);

            return;
        }

        $result = (new PhpFpmReloader())->reload();

        $this->assertTrue($result['signal']['attempted']);
        // Either succeeded (test env is root) or failed with an error
        // string — both shapes are acceptable, we're pinning that
        // reload() does not throw.
        $this->assertIsBool($result['signal']['ok']);
        if (! $result['signal']['ok']) {
            $this->assertIsString($result['signal']['error']);
            $this->assertStringContainsString('posix_kill(1, SIGUSR2) failed', $result['signal']['error']);
        }
    }

    public function test_reload_returns_shape_documented_in_docblock(): void
    {
        // The docblock advertises three top-level keys the caller can
        // inspect: enabled, http, signal. Pin the shape so a future
        // refactor cannot silently drop or rename them.
        config([
            'core_update.fpm_reload.enabled' => true,
            'core_update.fpm_reload.http_url' => '',
            'core_update.fpm_reload.http_token' => '',
            'core_update.fpm_reload.signal_pid' => 0,
        ]);
        Http::fake();

        $result = (new PhpFpmReloader())->reload();

        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('http', $result);
        $this->assertArrayHasKey('signal', $result);
    }
}
