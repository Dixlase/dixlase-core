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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LockdownStatus;
use App\Services\LockdownService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lockdown check middleware
 *
 * Restrict access during lockdown
 *
 * Usage:
 *   ->middleware('lockdown')           // Check all types
 *   ->middleware('lockdown:admin')     // Check admin panel lockdown
 *   ->middleware('lockdown:api')       // Check API lockdown
 *   ->middleware('lockdown:login')     // Check login lockdown
 */
class CheckLockdown
{
    /**
     * Handle an incoming request.
     *
     * @param  string|null  $type  Lockdown type (null = all types)
     */
    public function handle(Request $request, Closure $next, ?string $type = null): Response
    {
        // Check auto-release
        LockdownService::checkAutoRelease();

        // Get lockdown status
        $lockdown = LockdownService::getStatus();

        if (! $lockdown) {
            return $next($request);
        }

        // Skip if type is specified and that type is not locked
        if ($type !== null && $lockdown->type !== LockdownStatus::TYPE_FULL && $lockdown->type !== $type) {
            return $next($request);
        }

        // Check access permission
        $member = Auth::guard('member')->user();
        $ip = $request->ip();

        if (LockdownService::isAccessAllowed($ip, $member, $type)) {
            return $next($request);
        }

        // Response during lockdown
        return $this->lockdownResponse($request, $lockdown);
    }

    /**
     * Generate response during lockdown
     */
    protected function lockdownResponse(Request $request, LockdownStatus $lockdown): Response
    {
        $message = $lockdown->reason ?: __('admin/lockdown.default_message');

        // Return JSON response for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'lockdown',
                'message' => $message,
                'type' => $lockdown->type,
            ], 503);
        }

        // Display lockdown page for normal requests
        return response()->view('errors.lockdown', [
            'lockdown' => $lockdown,
            'message' => $message,
        ], 503);
    }
}
