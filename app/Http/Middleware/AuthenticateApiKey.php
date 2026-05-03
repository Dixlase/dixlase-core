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

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API key authentication middleware
 *
 * Retrieves key from Authorization: Bearer dxl_live_xxx header and
 * validates API key validity, scope, and IP restrictions
 *
 * Usage examples:
 *   - Route::middleware('auth.api') ... all scopes allowed
 *   - Route::middleware('auth.api:read:content') ... read:content scope required
 */
class AuthenticateApiKey
{
    /**
     * Handle the request
     *
     * @param  string  ...$scopes  Required scopes (middleware parameter)
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return $this->unauthorizedResponse(__('http/middleware/authenticate_api_key.api_key_not_provided'));
        }

        $apiKey = ApiKey::validate($bearerToken);

        if (! $apiKey) {
            return $this->unauthorizedResponse(__('http/middleware/authenticate_api_key.invalid_or_expired_api_key'));
        }

        // IP restriction check
        if (! $apiKey->allowsIp($request->ip())) {
            return $this->forbiddenResponse(__('http/middleware/authenticate_api_key.ip_address_access_not_allowed'));
        }

        // Scope check
        foreach ($scopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return $this->forbiddenResponse(__('http/middleware/authenticate_api_key.required_scope_missing', ['scope' => $scope]));
            }
        }

        // Update usage record
        $apiKey->recordUsage();

        // Set API key to request attribute
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    /**
     * Generate 401 Unauthorized response
     */
    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Generate 403 Forbidden response
     */
    protected function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'forbidden',
            'message' => $message,
        ], 403);
    }
}
