<?php

namespace App\Support;

/**
 * Signature verification result
 */
class SignatureResult
{
    /**
     * Error codes
     */
    public const ERROR_MISSING_HEADERS = 'missing_headers';
    public const ERROR_TIMESTAMP_EXPIRED = 'timestamp_expired';
    public const ERROR_INVALID_API_KEY = 'invalid_api_key';
    public const ERROR_UNSUPPORTED_VERSION = 'unsupported_version';
    public const ERROR_INVALID_SIGNATURE = 'invalid_signature';
    public const ERROR_REVOKED_KEY = 'revoked_key';

    public function __construct(
        public readonly bool $valid,
        public readonly ?string $error = null,
        public readonly ?string $message = null
    ) {}

    /**
     * Check if signature is valid
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get error code
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get error message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Create a success result
     */
    public static function success(): self
    {
        return new self(true);
    }

    /**
     * Create a failure result
     */
    public static function failure(string $error, string $message): self
    {
        return new self(false, $error, $message);
    }

    /**
     * Create missing headers error
     */
    public static function missingHeaders(): self
    {
        return self::failure(
            self::ERROR_MISSING_HEADERS,
            __('api.signature.errors.missing_headers')
        );
    }

    /**
     * Create timestamp expired error
     */
    public static function timestampExpired(): self
    {
        return self::failure(
            self::ERROR_TIMESTAMP_EXPIRED,
            __('api.signature.errors.timestamp_expired')
        );
    }

    /**
     * Create invalid API key error
     */
    public static function invalidApiKey(): self
    {
        return self::failure(
            self::ERROR_INVALID_API_KEY,
            __('api.signature.errors.invalid_api_key')
        );
    }

    /**
     * Create unsupported version error
     */
    public static function unsupportedVersion(): self
    {
        return self::failure(
            self::ERROR_UNSUPPORTED_VERSION,
            __('api.signature.errors.unsupported_version')
        );
    }

    /**
     * Create invalid signature error
     */
    public static function invalidSignature(): self
    {
        return self::failure(
            self::ERROR_INVALID_SIGNATURE,
            __('api.signature.errors.invalid_signature')
        );
    }

    /**
     * Create revoked key error
     */
    public static function revokedKey(): self
    {
        return self::failure(
            self::ERROR_REVOKED_KEY,
            __('api.signature.errors.revoked_key')
        );
    }

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        if ($this->valid) {
            return ['valid' => true];
        }

        return [
            'error' => [
                'code' => $this->error,
                'message' => $this->message,
            ],
        ];
    }
}
