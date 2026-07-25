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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * File Change DTO
 *
 * Immutable data object that holds file change information
 */
final readonly class FileChangeDTO implements JsonSerializable
{
    public const TYPE_CHANGED = 'changed';

    public const TYPE_ADDED = 'added';

    public const TYPE_REMOVED = 'removed';

    public const TYPE_SUSPICIOUS = 'suspicious';

    /**
     * @param  string  $path  File path
     * @param  string  $type  Change type (changed, added, removed, suspicious)
     * @param  string|null  $oldHash  Hash before change
     * @param  string|null  $newHash  Hash after change
     * @param  string|null  $reason  Reason (for suspicious case)
     */
    public function __construct(
        public string $path,
        public string $type,
        public ?string $oldHash = null,
        public ?string $newHash = null,
        public ?string $reason = null,
    ) {}

    /**
     * Create a changed file
     *
     * @param  string  $path  File path
     * @param  string  $oldHash  Hash before change
     * @param  string  $newHash  Hash after change
     */
    public static function changed(string $path, string $oldHash, string $newHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_CHANGED,
            oldHash: $oldHash,
            newHash: $newHash,
        );
    }

    /**
     * Create an added file
     *
     * @param  string  $path  File path
     * @param  string  $newHash  Hash
     */
    public static function added(string $path, string $newHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_ADDED,
            newHash: $newHash,
        );
    }

    /**
     * Create a removed file
     *
     * @param  string  $path  File path
     * @param  string  $oldHash  Hash before removal
     */
    public static function removed(string $path, string $oldHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_REMOVED,
            oldHash: $oldHash,
        );
    }

    /**
     * Create a suspicious file
     *
     * @param  string  $path  File path
     * @param  string  $hash  Hash
     * @param  string  $reason  Reason
     */
    public static function suspicious(string $path, string $hash, string $reason): self
    {
        return new self(
            path: $path,
            type: self::TYPE_SUSPICIOUS,
            newHash: $hash,
            reason: $reason,
        );
    }

    /**
     * Is change type
     */
    public function isChanged(): bool
    {
        return $this->type === self::TYPE_CHANGED;
    }

    /**
     * Is added type
     */
    public function isAdded(): bool
    {
        return $this->type === self::TYPE_ADDED;
    }

    /**
     * Is removed type
     */
    public function isRemoved(): bool
    {
        return $this->type === self::TYPE_REMOVED;
    }

    /**
     * Is suspicious type
     */
    public function isSuspicious(): bool
    {
        return $this->type === self::TYPE_SUSPICIOUS;
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'path' => $this->path,
            'type' => $this->type,
            'old_hash' => $this->oldHash,
            'new_hash' => $this->newHash,
            'reason' => $this->reason,
        ], fn ($v) => $v !== null);
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Generate DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'],
            type: $data['type'],
            oldHash: $data['old_hash'] ?? null,
            newHash: $data['new_hash'] ?? null,
            reason: $data['reason'] ?? null,
        );
    }
}
