<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * Signature verification result DTO
 *
 * Represents the Core signature verification result
 * Converted from DixlaseDevKit plugin's SignatureResult,
 * or generated directly from stub implementation
 */
final readonly class SignatureVerificationResult implements JsonSerializable
{
    /**
     * Status constants
     */
    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_UNSIGNED = 'unsigned';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_UNKNOWN_KEY = 'unknown_key';

    public const STATUS_ERROR = 'error';

    public const STATUS_PENDING = 'pending_verification';

    /**
     * @param  string  $status  Verification status
     * @param  string|null  $type  Signature type (official, verified, partner)
     * @param  string|null  $signedBy  Signer
     * @param  string|null  $signedAt  Signature timestamp
     * @param  string|null  $keyId  Key ID
     * @param  string|null  $keyLabel  Key label
     * @param  string|null  $message  Message
     * @param  array  $errors  Error list
     * @param  bool  $waived  Operator waiver overlay (suppresses the warning;
     *                        does NOT change $status). Set by the integration
     *                        layer (PluginPermissionService), not the verifier.
     */
    public function __construct(
        public string $status,
        public ?string $type = null,
        public ?string $signedBy = null,
        public ?string $signedAt = null,
        public ?string $keyId = null,
        public ?string $keyLabel = null,
        public ?string $message = null,
        public array $errors = [],
        public bool $waived = false,
    ) {}

    /**
     * Whether the signature is valid
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    /**
     * Whether it is unsigned
     */
    public function isUnsigned(): bool
    {
        return $this->status === self::STATUS_UNSIGNED;
    }

    /**
     * Whether an operator has waived the signature requirement (overlay).
     *
     * Orthogonal to $status: a waived result may still be invalid/unsigned
     * underneath; the waiver only means the operator accepted it deliberately.
     */
    public function isWaived(): bool
    {
        return $this->waived;
    }

    /**
     * Return a copy with the waiver overlay applied (this DTO is readonly).
     */
    public function withWaived(bool $waived = true): self
    {
        return new self(
            status: $this->status,
            type: $this->type,
            signedBy: $this->signedBy,
            signedAt: $this->signedAt,
            keyId: $this->keyId,
            keyLabel: $this->keyLabel,
            message: $this->message,
            errors: $this->errors,
            waived: $waived,
        );
    }

    /**
     * Whether the signature is invalid (possible tampering)
     */
    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    /**
     * Generate unsigned result
     */
    public static function unsigned(?string $message = null): self
    {
        return new self(
            status: self::STATUS_UNSIGNED,
            message: $message ?? 'No signature.',
        );
    }

    /**
     * Generate pending verification result
     */
    public static function pending(?string $message = null): self
    {
        return new self(
            status: self::STATUS_PENDING,
            message: $message ?? 'Signature verification module is unavailable.',
        );
    }

    /**
     * Generate valid signature result
     */
    public static function valid(string $keyId, ?string $keyLabel = null, ?string $signedBy = null, ?string $signedAt = null, ?string $type = null): self
    {
        return new self(
            status: self::STATUS_VALID,
            type: $type,
            signedBy: $signedBy,
            signedAt: $signedAt,
            keyId: $keyId,
            keyLabel: $keyLabel,
            message: 'Signature is valid.',
        );
    }

    /**
     * Generate invalid signature result
     */
    public static function invalid(?string $message = null, array $errors = []): self
    {
        return new self(
            status: self::STATUS_INVALID,
            message: $message ?? 'Signature is invalid.',
            errors: $errors,
        );
    }

    /**
     * Generate error result
     */
    public static function error(string $message, array $errors = []): self
    {
        return new self(
            status: self::STATUS_ERROR,
            message: $message,
            errors: $errors,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'type' => $this->type,
            'signed_by' => $this->signedBy,
            'signed_at' => $this->signedAt,
            'key_id' => $this->keyId,
            'key_label' => $this->keyLabel,
            'message' => $this->message,
            'errors' => $this->errors,
            'waived' => $this->waived,
        ];
    }
}
