<?php

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
     * @param Request $request
     * @param Closure $next
     * @param string|null $clientResolver Custom client resolver class (optional)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $clientResolver = null): Response
    {
        $result = $this->verifySignature($request, $clientResolver);

        if (!$result->isValid()) {
            $this->logFailure($request, $result);
            return $this->errorResponse($result);
        }

        return $next($request);
    }

    /**
     * Verify the request signature
     *
     * @param Request $request
     * @param string|null $clientResolver
     * @return SignatureResult
     */
    protected function verifySignature(Request $request, ?string $clientResolver = null): SignatureResult
    {
        // Extract headers
        $apiKey = $request->header(self::HEADER_KEY);
        $timestamp = $request->header(self::HEADER_TIMESTAMP);
        $signature = $request->header(self::HEADER_SIGNATURE);

        // Check required headers
        if (!$apiKey || !$timestamp || !$signature) {
            return SignatureResult::missingHeaders();
        }

        // Validate timestamp format and freshness
        if (!is_numeric($timestamp)) {
            return SignatureResult::timestampExpired();
        }

        $timestampInt = (int) $timestamp;
        if (!DixlaseSigner::isTimestampValid($timestampInt)) {
            return SignatureResult::timestampExpired();
        }

        // Get client and secret
        $clientData = $this->resolveClient($apiKey, $clientResolver);
        if ($clientData === null) {
            return SignatureResult::invalidApiKey();
        }

        if (!$clientData['active']) {
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

        if (!hash_equals($expectedSignature, $signatureValue)) {
            return SignatureResult::invalidSignature();
        }

        // Store client data in request for later use
        $request->attributes->set('dixlase_client', $clientData);

        return SignatureResult::success();
    }

    /**
     * Resolve client data from API key
     *
     * @param string $apiKey
     * @param string|null $resolverClass
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
     *
     * @param Request $request
     * @param SignatureResult $result
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
     *
     * @param string|null $apiKey
     * @return string
     */
    protected function maskApiKey(?string $apiKey): string
    {
        if (!$apiKey) {
            return '(none)';
        }

        if (strlen($apiKey) <= 12) {
            return str_repeat('*', strlen($apiKey));
        }

        return substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
    }

    /**
     * Create error response
     *
     * @param SignatureResult $result
     * @return Response
     */
    protected function errorResponse(SignatureResult $result): Response
    {
        return response()->json($result->toArray(), 401);
    }
}
