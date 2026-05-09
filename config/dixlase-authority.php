<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    | Authority API URL
    |--------------------------------------------------------------------------
    |
    | URL of the official Dixlase key management site. Used to retrieve the public
    | key for plugin signatures. In production, specify https://keys.dixlase.net (default).
    | Override via env only when using a different Authority in development environments.
    |
    */
    'url' => env('DIXLASE_AUTHORITY_URL', 'https://keys.dixlase.net'),

    /*
    |--------------------------------------------------------------------------
    | Cache validity period (hours)
    |--------------------------------------------------------------------------
    |
    | How long to trust public keys cached in the local DB.
    | Keys older than this period will attempt a re-fetch on the next verification (will
    | continue with old cache if the fetch fails).
    |
    */
    'cache_ttl_hours' => (int) env('DIXLASE_AUTHORITY_CACHE_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Fetch timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | HTTP timeout for retrieving public keys. Too short will fail on network delays,
    | too long will delay plugin installation.
    |
    */
    'fetch_timeout_seconds' => (int) env('DIXLASE_AUTHORITY_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | SSL certificate verification
    |--------------------------------------------------------------------------
    |
    | Set to false only when using an Authority with self-signed cert in sandbox or
    | internal verification. In production, must always be true (with CA verification).
    |
    */
    'verify_ssl' => filter_var(env('DIXLASE_AUTHORITY_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),

];
