<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\Extension;

use App\DTO\Extension\ReleaseInfo;

/**
 * Extension Source Provider Interface
 *
 * Defines the contract for extension source providers (GitHub, Marketplace, etc.).
 * Each provider implements this interface to enable downloading and updating
 * plugins and themes from external sources.
 */
interface ExtensionSourceInterface
{
    /**
     * Get the provider type identifier
     *
     * @return string e.g. "github", "marketplace", "composer"
     */
    public function getType(): string;

    /**
     * Get the human-readable label for this source
     */
    public function getLabel(): string;

    /**
     * List available plugins from this source
     *
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string}>
     */
    public function listPlugins(): array;

    /**
     * List available themes from this source
     *
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string}>
     */
    public function listThemes(): array;

    /**
     * Get the latest release info for an extension
     *
     * @param  string  $slug  Extension slug (kebab-case)
     * @param  string  $extensionType  "plugin" or "theme"
     */
    public function getLatestRelease(string $slug, string $extensionType = 'plugin'): ?ReleaseInfo;

    /**
     * Download a specific release and return the local ZIP file path
     *
     * @param  string  $slug  Extension slug (kebab-case)
     * @param  string  $version  Semantic version string
     * @param  string  $extensionType  "plugin" or "theme"
     * @return string Absolute path to downloaded ZIP file
     */
    public function downloadRelease(string $slug, string $version, string $extensionType = 'plugin'): string;

    /**
     * Check if the source is available (connectivity + authentication)
     */
    public function isAvailable(): bool;

    /**
     * Run a connection test and return detailed results
     *
     * @return array{success: bool, message: string, details: array<string, mixed>}
     */
    public function checkConnection(): array;
}
