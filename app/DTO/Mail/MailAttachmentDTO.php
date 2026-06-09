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

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * Mail Attachment DTO
 *
 * Immutable data object that holds mail attachment information
 */
final readonly class MailAttachmentDTO implements JsonSerializable
{
    /**
     * @param  string  $path  File path
     * @param  string|null  $name  Display name (uses filename if null)
     * @param  string|null  $mime  MIME type (auto-detected if null)
     */
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?string $mime = null,
    ) {}

    /**
     * Create from file path
     *
     * @param  string  $path  File path
     * @param  string|null  $name  Display name
     */
    public static function fromPath(string $path, ?string $name = null): self
    {
        return new self(
            path: $path,
            name: $name ?? basename($path),
        );
    }

    /**
     * Create from storage path
     *
     * @param  string  $storagePath  Storage relative path
     * @param  string|null  $name  Display name
     */
    public static function fromStorage(string $storagePath, ?string $name = null): self
    {
        return new self(
            path: storage_path('app/'.$storagePath),
            name: $name ?? basename($storagePath),
        );
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * Check if file exists
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Get file size
     */
    public function getSize(): ?int
    {
        if (! $this->exists()) {
            return null;
        }

        return filesize($this->path) ?: null;
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'name' => $this->getDisplayName(),
            'mime' => $this->mime,
            'size' => $this->getSize(),
        ];
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
     * Create DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'],
            name: $data['name'] ?? null,
            mime: $data['mime'] ?? null,
        );
    }
}
