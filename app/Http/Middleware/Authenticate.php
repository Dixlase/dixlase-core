<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * In Laravel 10/11, the $guards parameter is passed in the form "[guard1, guard2, ...]"
     * If nothing is specified, the default guard is used
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // First, delegate to the parent class handle() method
        // The parent class calls "$this->authenticate($request, $guards)" and invokes unauthenticated() if not authenticated
        // → unauthenticated() calls redirectTo($request)

        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Where to redirect when unauthenticated with the specified guard
     *
     * Called by the parent class's unauthenticated() method
     *  → throw new AuthenticationException(..., $this->redirectTo($request));
     */
    protected function redirectTo(Request $request)
    {
        // Return a 401 (Unauthorized) response for JSON requests
        if ($request->expectsJson()) {
            return;
        }

        // Redirect to "admin.login" when accessing admin panel URLs (including dynamically generated admin panel URLs)
        if (\App\Helpers\AdminHelper::isAdminRequest($request)) {
            return route('admin.login');
        }

        // Otherwise redirect to "/login"
        return route('admin.login');
    }
}
