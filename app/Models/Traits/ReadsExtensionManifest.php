<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

declare(strict_types=1);

namespace App\Models\Traits;

/**
 * Reads the version an extension declares in its manifest on disk.
 *
 * The recorded `version` column is written only by the install and update
 * paths, so an extension whose files arrive any other way -- a git deploy, an
 * operator replacing a directory -- keeps whatever version was recorded when
 * it was first installed (dixlase-core#521). This trait is the on-disk half
 * of that comparison; ExtensionVersionReconciler is what reconciles the two.
 *
 * The reader mirrors VersionDriftService::readManifestVersionFromDisk(), which
 * does the same job for core's own dixlase.json. The directory it reads from
 * comes from `config('plugins.plugins_directory')` /
 * `config('themes.theme_directory')`, as PluginHelper::getPluginPath() does,
 * so a test points it at a throwaway directory instead of the real
 * `plugins/` or `themes/`.
 *
 * Using models must implement manifestPath() and have a `directory` column.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait ReadsExtensionManifest
{
    /**
     * Absolute path of this extension's manifest, or null when the directory
     * cannot be resolved.
     */
    abstract public function manifestPath(): ?string;

    /**
     * The version declared by the manifest on disk.
     *
     * Returns null when the directory cannot be resolved, or the file is
     * missing, unreadable, not valid JSON, or has no non-empty string
     * "version" -- so a caller falls back to the recorded version instead of
     * treating an unreadable manifest as "no version".
     */
    public function versionOnDisk(): ?string
    {
        $path = $this->manifestPath();

        if ($path === null || ! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! is_string($decoded['version'] ?? null)) {
            return null;
        }

        $version = trim($decoded['version']);

        return $version !== '' ? $version : null;
    }
}
