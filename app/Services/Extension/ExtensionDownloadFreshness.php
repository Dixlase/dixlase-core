<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services\Extension;

/**
 * Whether a downloaded but not yet installed plugin or theme is older than
 * the latest release on the source it came from.
 *
 * Installing a download installs the files already on disk; a release
 * published in between -- possibly a security fix -- would otherwise be
 * skipped without a word, and the update check only covers installed
 * extensions (dixlase-core#456). Only downloads that carry a source sidecar
 * are checked: an uploaded ZIP or a git clone has no source to compare with.
 */
class ExtensionDownloadFreshness
{
    public function __construct(
        private ExtensionSourceManager $sources,
        private ExtensionSourceSidecar $sidecar,
    ) {}

    /**
     * The newer release's version, or null when the download is current or
     * cannot be compared.
     *
     * @param  'plugin'|'theme'  $kind
     */
    public function newerRelease(string $kind, string $directory, ?string $slug, ?string $downloadedVersion): ?string
    {
        if ($slug === null || $slug === '' || $downloadedVersion === null || $downloadedVersion === '') {
            return null;
        }

        try {
            $linkage = $this->sidecar->read(base_path(($kind === 'theme' ? 'themes/' : 'plugins/').$directory));
            if ($linkage === null) {
                return null;
            }

            $latest = $this->sources->latestReleaseVersionFor($slug, $kind, (int) $linkage['source_id']);
        } catch (\Throwable) {
            return null;
        }

        return $latest !== null && version_compare(ltrim($latest, 'vV'), ltrim($downloadedVersion, 'vV'), '>')
            ? $latest
            : null;
    }

    /**
     * The `available_version` an install should record, so the update badge
     * shows straight away instead of after the next update check
     * (dixlase-core#486). It is the newer release newerRelease() already
     * found, and only when the installed row is linked to the source that
     * answered: the update check clears the badge for an extension it cannot
     * update, so a row linked elsewhere, or not at all, starts without one.
     *
     * @param  ?array<string, mixed>  $sidecar  The download sidecar newerRelease() read
     * @param  ?array<string, mixed>  $linkage  The linkage the install records on the row
     */
    public function availableVersionToRecord(?string $newer, ?array $sidecar, ?array $linkage): ?string
    {
        if ($newer === null || ! isset($sidecar['source_id'], $linkage['source_id'])) {
            return null;
        }

        return (int) $sidecar['source_id'] === (int) $linkage['source_id'] ? $newer : null;
    }
}
