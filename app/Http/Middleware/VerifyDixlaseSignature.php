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

use App\Support\DixlaseSigner;
use App\Support\SignatureResult;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify Dixlase API signatures
 *
 * Validates HMAC-SHA256 signatures on incoming API requests.
 * Follows Dixlase API Signature Specification v1.
 *
 * @see docs/api-signature-spec.md
 */
class VerifyDixlaseSignature
{
    /**
     * Header names
     */
    protected const HEADER_KEY = 'X-Dixlase-Key';

    protected const HEADER_TIMESTAMP = 'X-Dixlase-Timestamp';

    protected const HEADER_SIGNATURE = 'X-Dixlase-Signature';

    /**
     * Handle an incoming request.
     *
     * @param  string|null  $clientResolver  Custom client resolver class (optional)
     */
    public function handle(Request $request, Closure $next, ?string $clientResolver = null): Response
    {
        $result = $this->verifySignature($request, $clientResolver);

        if (! $result->isValid()) {
            $this->logFailure($request, $result);

            return $this->errorResponse($result);
        }

        return $next($request);
    }

    /**
     * Verify the request signature
     */
    protected function verifySignature(Request $request, ?string $clientResolver = null): SignatureResult
    {
        // Extract headers
        $apiKey = $request->header(self::HEADER_KEY);
        $timestamp = $request->header(self::HEADER_TIMESTAMP);
        $signature = $request->header(self::HEADER_SIGNATURE);

        // Check required headers
        if (! $apiKey || ! $timestamp || ! $signature) {
            return SignatureResult::missingHeaders();
        }

        // Validate timestamp format and freshness
        if (! is_numeric($timestamp)) {
            return SignatureResult::timestampExpired();
        }

        $timestampInt = (int) $timestamp;
        if (! DixlaseSigner::isTimestampValid($timestampInt)) {
            return SignatureResult::timestampExpired();
        }

        // Get client and secret
        $clientData = $this->resolveClient($apiKey, $clientResolver);
        if ($clientData === null) {
            return SignatureResult::invalidApiKey();
        }

        if (! $clientData['active']) {
            return SignatureResult::revokedKey();
        }

        // Parse signature
        $parsed = DixlaseSigner::parseSignature($signature);
        if ($parsed === null) {
            return SignatureResult::invalidSignature();
        }

        [$version, $signatureValue] = $parsed;

        // Check version
        if ($version !== DixlaseSigner::VERSION) {
            return SignatureResult::unsupportedVersion();
        }

        // Verify signature
        $method = $request->method();
        $path = $request->getPathInfo();
        $body = $request->getContent();

        $expectedSignature = DixlaseSigner::generateSignature(
            $method,
            $path,
            $body,
            $clientData['secret'],
            $timestampInt
        );

        if (! hash_equals($expectedSignature, $signatureValue)) {
            return SignatureResult::invalidSignature();
        }

        // Store client data in request for later use
        $request->attributes->set('dixlase_client', $clientData);

        return SignatureResult::success();
    }

    /**
     * Resolve client data from API key
     *
     * @return array|null ['id' => int, 'secret' => string, 'active' => bool, ...]
     */
    protected function resolveClient(string $apiKey, ?string $resolverClass = null): ?array
    {
        // Use custom resolver if provided
        if ($resolverClass && class_exists($resolverClass)) {
            $resolver = app($resolverClass);
            if (method_exists($resolver, 'resolve')) {
                return $resolver->resolve($apiKey);
            }
        }

        // Default: Use ApiClient model if exists
        // This will be implemented when ApiClient model is created
        // For now, return null (no client found)

        // Example implementation when ApiClient model exists:
        // $client = \App\Models\ApiClient::where('api_key', $apiKey)->first();
        // if (!$client) {
        //     return null;
        // }
        // return [
        //     'id' => $client->id,
        //     'name' => $client->name,
        //     'secret' => $client->secret,
        //     'active' => $client->isActive(),
        // ];

        return null;
    }

    /**
     * Log signature verification failure
     */
    protected function logFailure(Request $request, SignatureResult $result): void
    {
        Log::channel('admin_error')->warning('API signature verification failed', [
            'error' => $result->getError(),
            'ip' => $request->ip(),
            'path' => $request->getPathInfo(),
            'method' => $request->method(),
            'api_key' => $this->maskApiKey($request->header(self::HEADER_KEY)),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Mask API key for logging
     */
    protected function maskApiKey(?string $apiKey): string
    {
        if (! $apiKey) {
            return '(none)';
        }

        if (strlen($apiKey) <= 12) {
            return str_repeat('*', strlen($apiKey));
        }

        return substr($apiKey, 0, 8).'...'.substr($apiKey, -4);
    }

    /**
     * Create error response
     */
    protected function errorResponse(SignatureResult $result): Response
    {
        return response()->json($result->toArray(), 401);
    }
}
