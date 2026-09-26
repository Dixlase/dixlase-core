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

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * End the session of a member whose account may no longer authenticate.
 *
 * Login checks the account state once, at the door. Nothing re-checked it
 * afterwards, so a member an administrator deactivated kept a live session
 * until it idled out -- and, with a remember-me cookie (passkey logins always
 * set one), the guard silently logged them back in on every visit. This runs
 * after `auth:member` on every member-authenticated route group.
 *
 * Logging out through the guard also cycles the remember token, so the
 * remember-me cookie stops working as well.
 */
class EnsureMemberCanAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('member');
        $member = $guard->user();

        if ($member instanceof Member && ! $member->canAuthenticate()) {
            $guard->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.account_inactive')], 401);
            }

            return redirect()->route('admin.login')
                ->withErrors(['login' => __('auth.account_inactive')]);
        }

        return $next($request);
    }
}
