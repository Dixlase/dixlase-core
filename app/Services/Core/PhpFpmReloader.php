<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Core;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Refresh opcache and realpath cache in the PHP-FPM SAPI after a core
 * update / rollback swap.
 *
 * Round 5 Finding D residual: `opcache_reset()` and
 * `clearstatcache(true)` are **per-SAPI** — calling them from the CLI
 * process that runs `dls:core:update` / `dls:core:rollback` does not
 * clear the caches inside PHP-FPM's workers, so requests that land in
 * the swap window can still see stale `require(...)` paths, a
 * momentarily-unregistered custom session driver, or a half-written
 * `vendor/composer/installed.php`. Round 4's opcache reset stayed and
 * catches the CLI side; this service adds the FPM-side reset by two
 * complementary paths:
 *
 *   1. HTTP self-request to a route that runs the resets **inside**
 *      the FPM SAPI. Works from any user (including a UI-triggered
 *      update spawned by an FPM worker as www-data). One request
 *      clears the shared opcache SHM for every worker; the receiving
 *      worker's realpath cache is cleared too, but other workers
 *      retain their per-process realpath cache for up to
 *      `realpath_cache_ttl` seconds — accepted, addressed further by
 *      Round 5 PR-Q (atomic swap).
 *
 *   2. `posix_kill($signal_pid, SIGUSR2)` for a graceful FPM master
 *      reload. Works from a **root** CLI (`docker exec` or a root
 *      cron); a www-data CLI (spawned by a UI update) does not have
 *      permission to signal PID 1 and the call silently fails. Clears
 *      per-worker realpath cache in one shot when it succeeds.
 *
 * Both paths are best-effort — a reload failure never aborts the
 * calling core operation; the operator is a manual FPM restart away
 * from the same effect. All attempts are logged so post-mortem can
 * tell which path (if any) succeeded.
 *
 * Config lives in `config/core_update.php` under the `fpm_reload`
 * block; defaults are safe (both paths enabled, PID 1, no HTTP unless
 * URL configured).
 */
class PhpFpmReloader
{
    /**
     * Reset opcache and realpath cache across all PHP-FPM workers,
     * best-effort. Returns a status array describing which paths were
     * attempted and their outcomes; the caller may log or ignore.
     *
     * The two paths run independently — one succeeding does not skip
     * the other, so the union of coverage is applied.
     *
     * @return array{
     *     enabled: bool,
     *     http: array{attempted: bool, ok: bool, status: int|null, error: string|null}|null,
     *     signal: array{attempted: bool, ok: bool, error: string|null}|null,
     * }
     */
    public function reload(): array
    {
        $result = [
            'enabled' => (bool) config('core_update.fpm_reload.enabled', true),
            'http' => null,
            'signal' => null,
        ];

        if (! $result['enabled']) {
            return $result;
        }

        $result['http'] = $this->tryHttp();
        $result['signal'] = $this->trySignal();

        Log::info('PhpFpmReloader::reload result', $result);

        return $result;
    }

    /**
     * @return array{attempted: bool, ok: bool, status: int|null, error: string|null}
     */
    protected function tryHttp(): array
    {
        $url = (string) config('core_update.fpm_reload.http_url', '');
        if ($url === '') {
            return ['attempted' => false, 'ok' => false, 'status' => null, 'error' => 'http_url not configured'];
        }

        $token = (string) config('core_update.fpm_reload.http_token', '');
        if ($token === '') {
            return ['attempted' => false, 'ok' => false, 'status' => null, 'error' => 'http_token not configured'];
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(2)
                ->withHeader('X-Fpm-Cache-Reset-Token', $token)
                ->post($url);

            $status = $response->status();

            return [
                'attempted' => true,
                'ok' => $response->successful(),
                'status' => $status,
                'error' => $response->successful() ? null : "unexpected HTTP status {$status}",
            ];
        } catch (\Throwable $e) {
            return [
                'attempted' => true,
                'ok' => false,
                'status' => null,
                'error' => 'exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{attempted: bool, ok: bool, error: string|null}
     */
    protected function trySignal(): array
    {
        if (! function_exists('posix_kill')) {
            return ['attempted' => false, 'ok' => false, 'error' => 'posix_kill() not available'];
        }

        $pid = (int) config('core_update.fpm_reload.signal_pid', 1);
        if ($pid < 1) {
            return ['attempted' => false, 'ok' => false, 'error' => 'signal_pid not configured (<= 0)'];
        }

        // SIGUSR2 for php-fpm master = graceful reload (drain workers,
        // master re-exec, opcache SHM re-init, per-worker realpath
        // cache cleared naturally by worker replacement).
        $signal = defined('SIGUSR2') ? SIGUSR2 : 12;

        // Suppress the warning that posix_kill() emits on EPERM; the
        // return value already captures success/failure and the
        // permission error is expected when the CLI runs as www-data
        // (UI-triggered update path).
        $ok = @posix_kill($pid, $signal);
        if (! $ok) {
            $errno = posix_get_last_error();
            $errstr = $errno !== 0 ? posix_strerror($errno) : 'unknown';

            return [
                'attempted' => true,
                'ok' => false,
                'error' => "posix_kill({$pid}, SIGUSR2) failed: {$errstr}",
            ];
        }

        return ['attempted' => true, 'ok' => true, 'error' => null];
    }
}
