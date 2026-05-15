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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * Baseline DTO
 *
 * Immutable data object that holds hash information used as the baseline for file integrity checks
 */
final readonly class BaselineDTO implements JsonSerializable
{
    /**
     * @param  string  $generatedAt  Generation datetime (ISO8601)
     * @param  string  $appVersion  Application version
     * @param  string  $hashAlgo  Hash algorithm
     * @param  string  $scope  Scope
     * @param  string|null  $identifier  Plugin/theme slug
     * @param  array<string>  $paths  Scan target path
     * @param  array<string>  $ignorePatterns  Exclusion patterns
     * @param  array<string,string>  $files  File path => hash value
     */
    public function __construct(
        public string $generatedAt,
        public string $appVersion,
        public string $hashAlgo,
        public string $scope,
        public ?string $identifier,
        public array $paths,
        public array $ignorePatterns,
        public array $files,
    ) {}

    /**
     * Get file count
     */
    public function getFileCount(): int
    {
        return count($this->files);
    }

    /**
     * Get hash of a specific file
     *
     * @param  string  $path  File path
     */
    public function getFileHash(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    /**
     * Check if file exists
     *
     * @param  string  $path  File path
     */
    public function hasFile(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'meta' => [
                'generated_at' => $this->generatedAt,
                'app_version' => $this->appVersion,
                'hash_algo' => $this->hashAlgo,
                'scope' => $this->scope,
                'identifier' => $this->identifier,
                'paths' => $this->paths,
                'ignore_patterns' => $this->ignorePatterns,
            ],
            'files' => $this->files,
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
        $meta = $data['meta'] ?? [];

        return new self(
            generatedAt: $meta['generated_at'] ?? now()->toIso8601String(),
            appVersion: $meta['app_version'] ?? config('app.version', '1.0.0'),
            hashAlgo: $meta['hash_algo'] ?? 'sha256',
            scope: $meta['scope'] ?? ScanTargetDTO::SCOPE_CORE,
            identifier: $meta['identifier'] ?? null,
            paths: $meta['paths'] ?? [],
            ignorePatterns: $meta['ignore_patterns'] ?? [],
            files: $data['files'] ?? [],
        );
    }

    /**
     * Get metadata only
     *
     * @return array<string,mixed>
     */
    public function getMeta(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'app_version' => $this->appVersion,
            'hash_algo' => $this->hashAlgo,
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'paths' => $this->paths,
            'ignore_patterns' => $this->ignorePatterns,
            'files_count' => $this->getFileCount(),
        ];
    }
}
