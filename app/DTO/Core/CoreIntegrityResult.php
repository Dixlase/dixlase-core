<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\DTO\Core;

use JsonSerializable;

/**
 * Result of a Core integrity verification.
 *
 * Status semantics differ from a plugin's binary valid/invalid because Core can
 * surface a file diff:
 *   - GENUINE : manifest signature valid AND files match (🟢)
 *   - MODIFIED: manifest signature valid but files differ — often a legitimate
 *               local customization (🟡); changed-file lists are populated
 *   - UNSIGNED: no manifest/signature present (⚪ not verifiable — core releases are not signed yet)
 *   - PENDING : trusted public key not yet available (offline / no cache)
 *   - INVALID : signature fails crypto verification or key_id mismatch (🔴)
 *   - ERROR   : sodium missing / unreadable manifest (⚠️)
 */
final readonly class CoreIntegrityResult implements JsonSerializable
{
    public const STATUS_GENUINE = 'genuine';

    public const STATUS_MODIFIED = 'modified';

    public const STATUS_UNSIGNED = 'unsigned';

    public const STATUS_PENDING = 'pending_verification';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_ERROR = 'error';

    /**
     * @param  array<int, string>  $mismatched  files whose hash differs
     * @param  array<int, string>  $missing  files in the manifest absent on disk
     * @param  array<int, string>  $extra  on-disk files not in the manifest
     */
    public function __construct(
        public string $status,
        public ?string $type = null,
        public ?string $keyId = null,
        public ?string $signedAt = null,
        public ?string $version = null,
        public array $mismatched = [],
        public array $missing = [],
        public array $extra = [],
        public ?string $message = null,
        public bool $waived = false,
    ) {}

    public function isGenuine(): bool
    {
        return $this->status === self::STATUS_GENUINE;
    }

    public function isModified(): bool
    {
        return $this->status === self::STATUS_MODIFIED;
    }

    public function isUnsigned(): bool
    {
        return $this->status === self::STATUS_UNSIGNED;
    }

    /**
     * Whether an operator has waived the core signature requirement (overlay).
     * Orthogonal to $status — a waived result may still be modified/unsigned.
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
            keyId: $this->keyId,
            signedAt: $this->signedAt,
            version: $this->version,
            mismatched: $this->mismatched,
            missing: $this->missing,
            extra: $this->extra,
            message: $this->message,
            waived: $waived,
        );
    }

    /**
     * Total number of changed files (mismatched + missing + extra).
     */
    public function changedCount(): int
    {
        return count($this->mismatched) + count($this->missing) + count($this->extra);
    }

    public static function genuine(string $keyId, ?string $type, ?string $signedAt, ?string $version): self
    {
        return new self(
            status: self::STATUS_GENUINE,
            type: $type,
            keyId: $keyId,
            signedAt: $signedAt,
            version: $version,
            message: 'Core is a genuine, unmodified release.',
        );
    }

    /**
     * @param  array<int, string>  $mismatched
     * @param  array<int, string>  $missing
     * @param  array<int, string>  $extra
     */
    public static function modified(string $keyId, ?string $type, ?string $signedAt, ?string $version, array $mismatched, array $missing, array $extra): self
    {
        return new self(
            status: self::STATUS_MODIFIED,
            type: $type,
            keyId: $keyId,
            signedAt: $signedAt,
            version: $version,
            mismatched: $mismatched,
            missing: $missing,
            extra: $extra,
            message: 'Core is an official release with local modifications.',
        );
    }

    public static function unsigned(?string $message = null): self
    {
        return new self(
            status: self::STATUS_UNSIGNED,
            message: $message ?? 'Core carries no signed manifest, so it cannot be checked against a signed release (core releases are not signed yet).',
        );
    }

    public static function pending(?string $keyId, ?string $message = null): self
    {
        return new self(
            status: self::STATUS_PENDING,
            keyId: $keyId,
            message: $message ?? 'Verification pending: trusted public key unavailable.',
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function invalid(string $message, array $context = []): self
    {
        return new self(
            status: self::STATUS_INVALID,
            keyId: $context['key_id'] ?? null,
            message: $message,
        );
    }

    public static function error(string $message): self
    {
        return new self(
            status: self::STATUS_ERROR,
            message: $message,
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
            'key_id' => $this->keyId,
            'signed_at' => $this->signedAt,
            'version' => $this->version,
            'changed_count' => $this->changedCount(),
            'mismatched' => $this->mismatched,
            'missing' => $this->missing,
            'extra' => $this->extra,
            'message' => $this->message,
            'waived' => $this->waived,
        ];
    }
}
