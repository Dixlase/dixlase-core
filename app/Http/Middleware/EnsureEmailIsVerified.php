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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class EnsureEmailIsVerified
{
    /**
     * Specify the redirect route for the middleware.
     *
     * @param  string  $route
     * @return string
     */
    public static function redirectTo($route)
    {
        return static::class.':'.$route;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        // Use member guard for admin panel
        // Note: For my page (user), use EnsureUserEmailIsVerified from the plugin side
        $guard = \App\Helpers\AdminHelper::isAdminRequest($request) ? 'member' : 'web';
        $user = auth($guard)->user();

        if (
            ! $user ||
            ($user instanceof MustVerifyEmail &&
                ! $user->hasVerifiedEmail())
        ) {
            // If not verified
            if ($request->expectsJson()) {
                return abort(403, 'Your email address is not verified.');
            }

            // For admin panel, redirect to dedicated unverified page
            if (\App\Helpers\AdminHelper::isAdminRequest($request)) {
                return redirect()->route('admin.verification.notice');
            }

            // If custom route is specified
            if ($redirectToRoute) {
                return Redirect::guest(URL::route($redirectToRoute));
            }

            // Default redirects to home
            return Redirect::guest('/');
        }

        return $next($request);
    }
}
