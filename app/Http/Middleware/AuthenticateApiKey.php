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

use App\Contracts\Site\SiteContextInterface;
use App\Facades\Audit;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * API key authentication middleware.
 *
 * Reads the bearer token from the Authorization header, validates it
 * against ApiKey, enforces IP allow-list and scope requirements, and
 * audits every use of a network-scope key.
 *
 * All error responses follow the unified API envelope documented in
 * docs/development/api-reference/versioning.md:
 *
 *   {"error": {"code": "...", "message": "..."}, "meta": {...}}
 *
 * Per the versioning spec error messages are always in English; clients
 * should map the stable `code` field to their own translation table
 * when localized text is needed.
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
            return $this->errorResponse(
                code: 'missing_credentials',
                status: 401,
                message: 'API key not provided.',
            );
        }

        $apiKey = ApiKey::validate($bearerToken);

        if (! $apiKey) {
            return $this->errorResponse(
                code: 'invalid_credentials',
                status: 401,
                message: 'Invalid or expired API key.',
            );
        }

        if (! $apiKey->allowsIp($request->ip())) {
            return $this->errorResponse(
                code: 'ip_not_allowed',
                status: 403,
                message: 'This IP address is not permitted to use this key.',
            );
        }

        foreach ($scopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return $this->errorResponse(
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
     * Build a JSON error response in the unified API envelope.
     *
     * @param  array<string, mixed>  $details  Optional structured payload (e.g. ['required_scope' => 'read:content'])
     */
    private function errorResponse(string $code, int $status, string $message, array $details = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json([
            'error' => $error,
            'meta' => $this->meta(),
        ], $status);
    }

    /**
     * Build the meta block carried by every API response.
     *
     * @return array{site_id: int|null, timestamp: string}
     */
    private function meta(): array
    {
        $siteId = null;
        try {
            $siteId = app(SiteContextInterface::class)->currentSiteId();
        } catch (Throwable) {
            // SiteContext may be unresolvable in edge cases (e.g. early
            // bootstrap, install flow). The meta block tolerates a null
            // site_id rather than failing the auth response.
        }

        return [
            'site_id' => $siteId,
            'timestamp' => now()->toIso8601String(),
        ];
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
