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

return [

    /*
    |--------------------------------------------------------------------------
    | Core update source-snapshot retention
    |--------------------------------------------------------------------------
    |
    | How many core-update source snapshots to keep under
    | storage/app/private/core-update/snapshots/. Each dls:core:update captures
    | one snapshot (the dls:core:rollback point) and, before this retention was
    | added, they accumulated indefinitely. After a successful update the oldest
    | snapshots (and their .meta.json sidecars) are pruned down to this count so
    | an operator keeps a short rollback history without unbounded disk growth.
    | Each snapshot is a copy of the core source tree, so keep this modest.
    |
    */

    'snapshot_retention' => (int) env('CORE_SNAPSHOT_RETENTION', 5),

    /*
    |--------------------------------------------------------------------------
    | PHP-FPM cache reload hook (Round 5 Finding D residual mitigation)
    |--------------------------------------------------------------------------
    |
    | opcache_reset() and clearstatcache(true) are per-SAPI — calling them
    | from the CLI process running dls:core:update / dls:core:rollback does
    | not clear the caches in PHP-FPM workers, so requests landing in the
    | swap window can still see stale `require(...)` paths or a briefly-
    | unregistered custom session driver. `\App\Services\Core\PhpFpmReloader`
    | refreshes the FPM SAPI just before the maintenance lift by two paths:
    |
    |   http_url + http_token: POST to an internal endpoint served IN the
    |     FPM SAPI (opcache SHM is shared across workers, so one hit is
    |     enough for opcache; the receiving worker's realpath cache is
    |     cleared, others retain up to `realpath_cache_ttl` seconds).
    |
    |   signal_pid: posix_kill($signal_pid, SIGUSR2) sends php-fpm master
    |     a graceful reload. Requires the CLI to have permission to signal
    |     the master (root — docker exec / root cron); a www-data CLI
    |     spawned by a UI-triggered update will silently fail here.
    |
    | Both paths are best-effort. Failures are logged, never fatal.
    |
    */
    'fpm_reload' => [
        'enabled' => (bool) env('CORE_FPM_RELOAD_ENABLED', true),
        'http_url' => env('CORE_FPM_RESET_URL', ''),
        'http_token' => env('CORE_FPM_RESET_TOKEN', ''),
        'signal_pid' => (int) env('CORE_FPM_RELOAD_SIGNAL_PID', 1),
    ],

];
