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

namespace App\Http\Controllers\System;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Internal endpoint {@see PhpFpmReloader} hits from CLI to reset
 * opcache and realpath cache inside the PHP-FPM SAPI.
 *
 * The endpoint is bare — outside the web / admin / api middleware
 * stacks and outside session/auth entirely. Access is token-gated via
 * `X-Fpm-Cache-Reset-Token`, matched against
 * `core_update.fpm_reload.http_token` (env `CORE_FPM_RESET_TOKEN`).
 * With no token configured the endpoint always returns 503 so a
 * misconfigured install cannot inadvertently expose the reset.
 *
 * `opcache_reset()` operates on the shared opcache SHM, so one call
 * from a single receiving worker refreshes every worker; the caller
 * can trust one request is enough for opcache. `clearstatcache(true)`
 * however is per-process, so it only clears the receiving worker's
 * realpath cache — other workers retain their cache up to
 * `realpath_cache_ttl` seconds. PhpFpmReloader documents this
 * limitation; Round 5 PR-Q (atomic swap) removes the window entirely.
 */
class FpmCacheResetController extends Controller
{
    public function reset(Request $request): Response
    {
        $expected = (string) config('core_update.fpm_reload.http_token', '');
        $provided = (string) $request->header('X-Fpm-Cache-Reset-Token', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            // Do not distinguish "no token configured" from "bad token"
            // — the caller has no legitimate reason to differentiate,
            // and merging the paths avoids an oracle that would confirm
            // the endpoint is armed.
            return new JsonResponse(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $opcacheReset = false;
        if (function_exists('opcache_reset')) {
            $opcacheReset = (bool) @opcache_reset();
        }

        // clearstatcache(true) also empties the realpath cache for the
        // current PHP process (this worker); no return value.
        clearstatcache(true);

        Log::info('FpmCacheResetController::reset', [
            'sapi' => PHP_SAPI,
            'pid' => function_exists('posix_getpid') ? posix_getpid() : null,
            'opcache_reset' => $opcacheReset,
        ]);

        return new JsonResponse([
            'ok' => true,
            'sapi' => PHP_SAPI,
            'opcache_reset' => $opcacheReset,
            'statcache_cleared' => true,
        ]);
    }
}
