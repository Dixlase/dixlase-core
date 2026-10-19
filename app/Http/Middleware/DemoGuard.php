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

use App\Services\Admin\ReadonlyRouteGuard;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks destructive admin actions when config('dixlase.demo_mode') is on.
 *
 * Designed for public demo deployments where many anonymous visitors share
 * the same Dixlase install. Which state-changing routes are read-only — and
 * whether this request/user is subject to the guard — is decided by
 * ReadonlyRouteGuard, which an extension can feed through the
 * ReadonlyRouteProviderInterface capability (it falls back to a built-in list
 * while none is registered). This middleware only turns a blocked request
 * into the appropriate 403 / redirect response.
 */
class DemoGuard
{
    public function __construct(protected ReadonlyRouteGuard $guard) {}

    public function handle(Request $request, Closure $next)
    {
        if ($this->guard->shouldBlock($request)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    protected function deny(Request $request)
    {
        $message = __('admin/demo.action_disabled');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
