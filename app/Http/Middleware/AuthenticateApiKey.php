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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Audit;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Support\Api\ApiErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API key authentication middleware.
 *
 * Reads the bearer token from the Authorization header, validates it
 * against ApiKey, enforces IP allow-list and scope requirements, and
 * audits every use of a network-scope key.
 *
 * Error responses are produced by ApiErrorResponse so the JSON envelope
 * stays in sync with the global exception handler in bootstrap/app.php.
 * See docs/development/api-reference/versioning.md for the contract.
 *
 * Usage examples:
 *   - Route::middleware('auth.api') ... all scopes allowed
 *   - Route::middleware('auth.api:read:content') ... read:content scope required
 */
class AuthenticateApiKey
{
    /**
     * Handle the request.
     *
     * @param  string  ...$scopes  Required scopes (middleware parameter)
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return ApiErrorResponse::make(
                code: 'missing_credentials',
                status: 401,
                message: 'API key not provided.',
            );
        }

        $apiKey = ApiKey::validate($bearerToken);

        if (! $apiKey) {
            return ApiErrorResponse::make(
                code: 'invalid_credentials',
                status: 401,
                message: 'Invalid or expired API key.',
            );
        }

        if (! $apiKey->allowsIp($request->ip())) {
            return ApiErrorResponse::make(
                code: 'ip_not_allowed',
                status: 403,
                message: 'This IP address is not permitted to use this key.',
            );
        }

        foreach ($scopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return ApiErrorResponse::make(
                    code: 'insufficient_scope',
                    status: 403,
                    message: "Required scope '{$scope}' is missing.",
                    details: ['required_scope' => $scope],
                );
            }
        }

        // Audit network-scope key usage. Network keys cross site boundaries
        // so each request that authenticates with one is recorded for
        // forensic traceability. NOTICE severity keeps the entry visible
        // without flooding the alert pipeline (creation already logs at
        // CRITICAL via dls:api:create-network-key).
        if ($apiKey->isNetworkKey()) {
            $this->auditNetworkKeyUsage($apiKey, $request);
        }

        $apiKey->recordUsage();
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    /**
     * Record a single use of a network-scope (site_id = null) API key.
     */
    private function auditNetworkKeyUsage(ApiKey $apiKey, Request $request): void
    {
        Audit::log([
            'action' => 'network_api_key_used',
            'category' => AuditLog::CATEGORY_SECURITY,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'outcome' => 'success',
            'context' => [
                'api_key_id' => $apiKey->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ],
        ]);
    }
}
