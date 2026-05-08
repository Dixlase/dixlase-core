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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
     * Get detailed information for a single extension (manifest + repository metadata)
     *
     * @param  string  $slug  Extension slug
     * @param  string  $extensionType  "plugin" or "theme"
     * @return array<string, mixed>|null
     */
    public function getExtensionDetails(string $slug, string $extensionType = 'plugin'): ?array;

    /**
     * Get the latest release info for an extension
     *
     * @param  string  $slug  Extension slug (kebab-case)
     * @param  string  $extensionType  "plugin" or "theme"
     */
    public function getLatestRelease(string $slug, string $extensionType = 'plugin'): ?ReleaseInfo;

    /**
     * Get the latest release info for the Dixlase Core itself.
     *
     * Returns null when the source either does not host a core release feed or
     * the request fails. Implementations should target a single, well-known
     * repository (the core repo) rather than the per-extension prefix scheme.
     */
    public function getLatestCoreRelease(): ?ReleaseInfo;

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
     * Download a specific Core release and return the local ZIP file path.
     *
     * Targets the core repository (no slug-prefix scheme). Throws on failure.
     *
     * @param  string  $version  Semantic version string (no leading "v")
     * @return string Absolute path to downloaded ZIP file
     */
    public function downloadCoreRelease(string $version): string;

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
