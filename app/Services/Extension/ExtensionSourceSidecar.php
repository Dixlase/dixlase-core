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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Remembers which extension source served a downloaded plugin / theme
 * between the download step and the install step.
 *
 * A download (admin "add from source" page, or `dls:plugin:download
 * --extract`) knows the ExtensionSource it pulled the ZIP from; the
 * install step that runs later — possibly from a different process or
 * a different UI — does not. This helper bridges the two with a small
 * JSON sidecar written next to plugin.json / theme.json inside the
 * extracted extension directory. The install step reads it back so
 * `source_id` / `source_repo` / `installation_method` /
 * `installed_from_url` land on the Plugin / Theme row, and deletes it
 * so the metadata is not committed to source control by accident if
 * the operator later versions plugins/ or themes/.
 *
 * The file is excluded from signature verification
 * (see CoreSignatureVerifier), so its presence never invalidates a
 * signed extension.
 *
 * Callers pass the absolute extension directory so the helper has no
 * opinion about where extensions live; that also lets tests point it
 * at a throwaway directory.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class ExtensionSourceSidecar
{
    /**
     * Sidecar file name, relative to the extension directory.
     */
    public const FILENAME = '.dixlase-source.json';

    /**
     * Write the linkage produced by ExtensionSourceManager next to the
     * extension manifest. Loss of the sidecar only degrades update-check
     * linkage, so a write failure is logged, never thrown.
     *
     * @param  array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}  $linkage
     */
    public function write(string $extensionDir, array $linkage): void
    {
        try {
            File::put(
                $this->path($extensionDir),
                json_encode($linkage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to write extension source sidecar', [
                'directory' => $extensionDir,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Read the sidecar without removing it. Returns null when there is
     * none (plain ZIP upload, git clone, bundled extension) or when the
     * file does not carry a source_id.
     *
     * @return ?array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}
     */
    public function read(string $extensionDir): ?array
    {
        $path = $this->path($extensionDir);
        if (! File::exists($path)) {
            return null;
        }

        try {
            $data = json_decode(File::get($path), true);
        } catch (\Throwable $e) {
            Log::warning('Failed to read extension source sidecar', [
                'directory' => $extensionDir,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($data) || ! isset($data['source_id'])) {
            return null;
        }

        return $data;
    }

    /**
     * Read the sidecar and remove it in the same step. This is what an
     * install step wants once it has persisted the linkage.
     *
     * @return ?array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}
     */
    public function consume(string $extensionDir): ?array
    {
        $data = $this->read($extensionDir);
        $this->delete($extensionDir);

        return $data;
    }

    /**
     * Remove the sidecar if present. Safe to call when there is none.
     */
    public function delete(string $extensionDir): void
    {
        $path = $this->path($extensionDir);
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function path(string $extensionDir): string
    {
        return rtrim($extensionDir, '/').'/'.self::FILENAME;
    }
}
