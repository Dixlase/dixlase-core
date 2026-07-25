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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mutual-TLS (client certificate) authentication middleware (Phase 1 stub).
 *
 * Reserved alias: `auth.mtls` (registered in `bootstrap/app.php`).
 *
 * Plugins that gate routes on a verified client certificate replace this
 * binding via the service container. Typical implementations read the
 * cert subject / issuer from headers populated by the upstream proxy
 * (`X-SSL-Client-S-DN`, `X-SSL-Client-Verify`, …) and reject the request
 * unless the cert chains to a trusted CA and matches a registered device.
 *
 * This stub aborts the request with HTTP 501 so any route mistakenly
 * applying `auth.mtls` without an integration plugin fails fast with a
 * clear message instead of silently allowing traffic. See
 * `docs/development/extension-points.md` for guidance.
 */
class AuthenticateMtls
{
    public function handle(Request $request, Closure $next): Response
    {
        abort(
            501,
            'auth.mtls is a reserved Dixlase extension point and is not implemented in this build. '.
            'Install a client-certificate / device-trust plugin (or rebind App\\Http\\Middleware\\AuthenticateMtls) '.
            'before applying this middleware. See docs/development/extension-points.md.'
        );
    }
}
