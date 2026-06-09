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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * Scan target DTO
 *
 * Immutable data object that defines the target for file integrity scanning
 */
final readonly class ScanTargetDTO implements JsonSerializable
{
    public const SCOPE_CORE = 'core';

    public const SCOPE_PLUGIN = 'plugin';

    public const SCOPE_THEME = 'theme';

    public const SCOPE_ALL = 'all';

    /**
     * @param  string  $scope  Scope (core, plugin, theme, all)
     * @param  string|null  $identifier  Plugin/theme slug (when scope=plugin/theme)
     * @param  array<string>  $paths  Scan target path
     * @param  array<string>  $ignorePatterns  Exclusion patterns
     * @param  string  $hashAlgo  Hash algorithm
     */
    public function __construct(
        public string $scope = self::SCOPE_CORE,
        public ?string $identifier = null,
        public array $paths = [],
        public array $ignorePatterns = [],
        public string $hashAlgo = 'sha256',
    ) {}

    /**
     * Generate target for Core scan
     */
    public static function core(): self
    {
        return new self(
            scope: self::SCOPE_CORE,
            paths: [
                'app',
                'bootstrap',
                'config',
                'routes',
                'resources',
                'database/migrations',
                'public/index.php',
                'artisan',
                'composer.json',
                'composer.lock',
            ],
            ignorePatterns: [
                'custom',
                'storage',
                'vendor',
                'node_modules',
                'bootstrap/cache',
                '.git',
                '.env',
                '.env.*',
                'public/uploads',
                'public/storage',
                'public/hot',
                '*.log',
            ],
        );
    }

    /**
     * Generate target for plugin scan
     *
     * @param  string  $pluginSlug  Plugin slug
     */
    public static function plugin(string $pluginSlug): self
    {
        return new self(
            scope: self::SCOPE_PLUGIN,
            identifier: $pluginSlug,
            paths: [
                "plugins/{$pluginSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
        );
    }

    /**
     * Generate target for theme scan
     *
     * @param  string  $themeSlug  Theme slug
     */
    public static function theme(string $themeSlug): self
    {
        return new self(
            scope: self::SCOPE_THEME,
            identifier: $themeSlug,
            paths: [
                "themes/{$themeSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
        );
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'paths' => $this->paths,
            'ignore_patterns' => $this->ignorePatterns,
            'hash_algo' => $this->hashAlgo,
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
     * Generate DTO from array
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            scope: $data['scope'] ?? self::SCOPE_CORE,
            identifier: $data['identifier'] ?? null,
            paths: $data['paths'] ?? [],
            ignorePatterns: $data['ignore_patterns'] ?? [],
            hashAlgo: $data['hash_algo'] ?? 'sha256',
        );
    }

    /**
     * Get baseline file name
     */
    public function getBaselineFilename(): string
    {
        return match ($this->scope) {
            self::SCOPE_PLUGIN => "plugin_{$this->identifier}_hashes.json",
            self::SCOPE_THEME => "theme_{$this->identifier}_hashes.json",
            default => 'core_hashes.json',
        };
    }
}
