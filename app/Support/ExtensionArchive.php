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

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Safe extraction of an uploaded or downloaded extension ZIP.
 *
 * The admin upload/download paths used to take the first top-level directory
 * as "the" extension, check only that one for an existing install, and then
 * extract the whole archive into plugins/ (or the theme directory). A ZIP with
 * a second top-level directory therefore landed an extra extension that the
 * pending-install marker never covered -- its autoload.files ran on the next
 * request -- and an entry such as DixlaseSEO/config/x.php overwrote a file of
 * an installed, enabled plugin. An archive must now hold exactly one
 * top-level directory, and only that directory is moved into place, from a
 * private staging directory.
 */
final class ExtensionArchive
{
    /**
     * The archive's single top-level directory, or null when the archive has
     * files at its root, more than one top-level directory, or an entry that
     * is not a plain relative path.
     */
    public static function singleRootDirectory(ZipArchive $zip): ?string
    {
        $root = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || $entry === '') {
                return null;
            }

            if (str_contains($entry, "\0") || str_contains($entry, '\\') || str_starts_with($entry, '/')) {
                return null;
            }

            $segments = explode('/', $entry);
            if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
                return null;
            }

            // A file directly at the archive root (no directory segment).
            if (count($segments) === 1) {
                return null;
            }

            if ($segments[0] === '') {
                return null;
            }

            if ($root === null) {
                $root = $segments[0];
            } elseif ($root !== $segments[0]) {
                return null;
            }
        }

        return $root;
    }

    /**
     * Extract the archive into a staging directory and move its single
     * top-level directory to $parentDir/$root. Nothing else in the archive
     * reaches $parentDir. Returns false when the destination already exists
     * or extraction fails.
     */
    public static function extractSingleRoot(ZipArchive $zip, string $root, string $parentDir): bool
    {
        $destination = rtrim($parentDir, '/').'/'.$root;
        if (File::exists($destination)) {
            return false;
        }

        $staging = storage_path('app/private/extension-staging/'.Str::random(24));
        File::ensureDirectoryExists($staging, 0700);

        try {
            if (! $zip->extractTo($staging) || ! is_dir($staging.'/'.$root)) {
                return false;
            }

            File::ensureDirectoryExists(rtrim($parentDir, '/'));

            // copyDirectory, not moveDirectory: rename() fails with EXDEV when
            // storage/ and the extension directory are on different
            // filesystems (bind mounts). The staging copy is removed below.
            return File::copyDirectory($staging.'/'.$root, $destination);
        } finally {
            File::deleteDirectory($staging);
        }
    }
}
