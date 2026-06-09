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

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Dixlase API Signature Helper
 *
 * Generates and verifies HMAC-SHA256 signatures for API requests and webhooks.
 * Follows Dixlase API Signature Specification v1.
 *
 * @see docs/api-signature-spec.md
 */
class DixlaseSigner
{
    /**
     * Current signature version
     */
    public const VERSION = 'v1';

    /**
     * Signature algorithm
     */
    public const ALGORITHM = 'sha256';

    /**
     * Default timestamp tolerance in seconds (5 minutes)
     */
    public const DEFAULT_TOLERANCE = 300;

    /**
     * API key prefix
     */
    public const KEY_PREFIX = 'dls';

    /**
     * Generate signature headers for an API request
     *
     * @param  string  $method  HTTP method (GET, POST, etc.)
     * @param  string  $path  Request path without query string
     * @param  string|null  $body  Raw request body
     * @param  string  $secret  API secret key
     * @param  int|null  $timestamp  Unix timestamp (defaults to current time)
     * @return array Headers array with X-Dixlase-Timestamp and X-Dixlase-Signature
     */
    public static function sign(
        string $method,
        string $path,
        ?string $body,
        string $secret,
        ?int $timestamp = null
    ): array {
        $timestamp = $timestamp ?? time();
        $signature = self::generateSignature($method, $path, $body, $secret, $timestamp);

        return [
            'X-Dixlase-Timestamp' => (string) $timestamp,
            'X-Dixlase-Signature' => self::VERSION.'='.$signature,
        ];
    }

    /**
     * Generate signature headers for a webhook delivery
     *
     * @param  string  $path  Webhook endpoint path
     * @param  string  $body  JSON payload
     * @param  string  $secret  Webhook secret
     * @param  int|null  $timestamp  Unix timestamp
     * @return array Headers array
     */
    public static function signWebhook(
        string $path,
        string $body,
        string $secret,
        ?int $timestamp = null
    ): array {
        return self::sign('POST', $path, $body, $secret, $timestamp);
    }

    /**
     * Verify a signature against expected values
     *
     * @param  string  $method  HTTP method
     * @param  string  $path  Request path
     * @param  string|null  $body  Raw request body
     * @param  string  $secret  API/Webhook secret
     * @param  int  $timestamp  Timestamp from header
     * @param  string  $signature  Signature from header (with version prefix)
     * @param  int  $tolerance  Timestamp tolerance in seconds
     * @return bool True if signature is valid
     */
    public static function verify(
        string $method,
        string $path,
        ?string $body,
        string $secret,
        int $timestamp,
        string $signature,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): bool {
        // Check timestamp freshness
        if (! self::isTimestampValid($timestamp, $tolerance)) {
            return false;
        }

        // Parse signature version and value
        $parsed = self::parseSignature($signature);
        if ($parsed === null) {
            return false;
        }

        [$version, $signatureValue] = $parsed;

        // Only support v1 for now
        if ($version !== self::VERSION) {
            return false;
        }

        // Generate expected signature
        $expected = self::generateSignature($method, $path, $body, $secret, $timestamp);

        // Constant-time comparison
        return hash_equals($expected, $signatureValue);
    }

    /**
     * Generate the raw signature value (without version prefix)
     *
     * @param  string  $method  HTTP method
     * @param  string  $path  Request path
     * @param  string|null  $body  Raw request body
     * @param  string  $secret  Secret key
     * @param  int  $timestamp  Unix timestamp
     * @return string Hexadecimal signature
     */
    public static function generateSignature(
        string $method,
        string $path,
        ?string $body,
        string $secret,
        int $timestamp
    ): string {
        $signingString = self::buildSigningString($method, $path, $body, $timestamp);

        return hash_hmac(self::ALGORITHM, $signingString, $secret);
    }

    /**
     * Build the signing string from request components
     *
     * @param  string  $method  HTTP method
     * @param  string  $path  Request path
     * @param  string|null  $body  Raw request body
     * @param  int  $timestamp  Unix timestamp
     * @return string Signing string
     */
    public static function buildSigningString(
        string $method,
        string $path,
        ?string $body,
        int $timestamp
    ): string {
        $bodyHash = self::hashBody($body);

        return implode("\n", [
            $timestamp,
            strtoupper($method),
            $path,
            $bodyHash,
        ]);
    }

    /**
     * Hash the request body
     *
     * @param  string|null  $body  Raw request body
     * @return string SHA-256 hash in hexadecimal
     */
    public static function hashBody(?string $body): string
    {
        return hash(self::ALGORITHM, $body ?? '');
    }

    /**
     * Check if a timestamp is within acceptable tolerance
     *
     * @param  int  $timestamp  Timestamp to check
     * @param  int  $tolerance  Tolerance in seconds
     * @return bool True if timestamp is valid
     */
    public static function isTimestampValid(int $timestamp, int $tolerance = self::DEFAULT_TOLERANCE): bool
    {
        $now = time();

        return abs($now - $timestamp) <= $tolerance;
    }

    /**
     * Parse a signature header value
     *
     * @param  string  $signature  Signature with version prefix (e.g., "v1=abc123")
     * @return array|null [version, signature] or null if invalid format
     */
    public static function parseSignature(string $signature): ?array
    {
        if (! str_contains($signature, '=')) {
            return null;
        }

        $parts = explode('=', $signature, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$version, $value] = $parts;

        if (empty($version) || empty($value)) {
            return null;
        }

        return [$version, $value];
    }

    /**
     * Generate a new API key
     *
     * @param  string  $type  Key type ('key' for API, 'wh' for webhook)
     * @return string Generated key (e.g., "dls_key_abc123...")
     */
    public static function generateApiKey(string $type = 'key'): string
    {
        $random = Str::random(32);

        return self::KEY_PREFIX.'_'.$type.'_'.$random;
    }

    /**
     * Generate a new secret
     *
     * @param  int  $length  Secret length (minimum 32)
     * @return string Generated secret
     */
    public static function generateSecret(int $length = 64): string
    {
        $length = max(32, $length);

        return Str::random($length);
    }

    /**
     * Validate API key format
     *
     * @param  string  $key  API key to validate
     * @return bool True if format is valid
     */
    public static function isValidKeyFormat(string $key): bool
    {
        // Format: dls_{type}_{random32}
        $pattern = '/^'.self::KEY_PREFIX.'_(key|wh)_[a-zA-Z0-9]{32}$/';

        return (bool) preg_match($pattern, $key);
    }

    /**
     * Get key type from API key
     *
     * @param  string  $key  API key
     * @return string|null Key type ('key' or 'wh') or null if invalid
     */
    public static function getKeyType(string $key): ?string
    {
        if (! self::isValidKeyFormat($key)) {
            return null;
        }

        $parts = explode('_', $key);

        return $parts[1] ?? null;
    }

    /**
     * Create a signature verification result
     *
     * @param  bool  $valid  Whether signature is valid
     * @param  string|null  $error  Error code if invalid
     * @param  string|null  $message  Error message if invalid
     */
    public static function createResult(bool $valid, ?string $error = null, ?string $message = null): SignatureResult
    {
        return new SignatureResult($valid, $error, $message);
    }
}
