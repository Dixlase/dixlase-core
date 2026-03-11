<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Middleware;

use App\Helpers\ConfigHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // Skip session config during installation to avoid DB access before tables are created
        if ($request->is('install/*') || $request->is('install')) {
            return $next($request);
        }

        // Determine the guard from the request context if not provided
        if ($guard === null) {
            // Check if this is an admin route
            if (\App\Helpers\AdminHelper::isAdminRequest($request)) {
                $guard = 'member';
            } else {
                // For future user management plugins
                $guard = Auth::getDefaultDriver();
            }
        }

        // Apply session configuration based on the guard
        try {
            ConfigHelper::applySessionConfig($guard);
        } catch (\Exception $e) {
            // If session config fails (e.g., during database operations), continue with defaults
        }

        return $next($request);
    }
}
