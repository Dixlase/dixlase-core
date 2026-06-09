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

namespace App\DTO\Licensing;

use DateTimeImmutable;
use JsonSerializable;

/**
 * License verification result DTO
 *
 * Represents the result of verifying a paid plugin's or theme's license
 * against the configured license authority. The shape is reserved for the
 * v0.1.0 release; the core ships only a no-op verifier (see
 * App\Contracts\Licensing\LicenseVerifierInterface) and a real implementation
 * is expected to be provided by a future official "DixlaseLicensing" plugin
 * once the marketplace begins selling paid extensions (Phase 2).
 *
 * Plugins and themes that need to react to license state (e.g. degrade
 * functionality when expired) should depend on this DTO and the verifier
 * contract rather than any concrete implementation.
 */
final readonly class LicenseVerificationResult implements JsonSerializable
{
    /**
     * The extension is licensed and the license is valid.
     */
    public const STATUS_VALID = 'valid';

    /**
     * The extension is licensed but the license has expired.
     */
    public const STATUS_EXPIRED = 'expired';

    /**
     * The license key is malformed, unknown to the authority, or revoked.
     */
    public const STATUS_INVALID = 'invalid';

    /**
     * No license is required (e.g. the extension is free / OSS).
     */
    public const STATUS_NOT_REQUIRED = 'not_required';

    /**
     * The extension declares a paid license but no key has been registered yet.
     */
    public const STATUS_UNLICENSED = 'unlicensed';

    /**
     * License verification is not available in this install
     * (no licensing plugin is installed). Treated as "do not enforce".
     */
    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * License verification is queued but no answer yet
     * (e.g. authority unreachable, retrying).
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Verification failed due to an internal error.
     */
    public const STATUS_ERROR = 'error';

    /**
     * @param  string  $status  One of the STATUS_* constants
     * @param  string|null  $licenseType  Free-form license tier identifier (e.g. "personal", "site", "agency")
     * @param  string|null  $licenseKeyId  Opaque identifier of the registered license key (never the raw key)
     * @param  DateTimeImmutable|null  $expiresAt  License expiry, if known
     * @param  string|null  $message  Human-readable message suitable for the admin UI
     * @param  array<int, string>  $errors  Machine-readable error codes (empty when status is valid)
     */
    public function __construct(
        public string $status,
        public ?string $licenseType = null,
        public ?string $licenseKeyId = null,
        public ?DateTimeImmutable $expiresAt = null,
        public ?string $message = null,
        public array $errors = [],
    ) {}

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isEnforcementRequired(): bool
    {
        return ! in_array(
            $this->status,
            [self::STATUS_VALID, self::STATUS_NOT_REQUIRED, self::STATUS_UNAVAILABLE],
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'license_type' => $this->licenseType,
            'license_key_id' => $this->licenseKeyId,
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
