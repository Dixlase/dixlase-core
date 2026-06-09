<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services\Extension;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Captures and restores a snapshot of an extension's directory tree
 * (plugin or theme) so that an update that fails partway through can be
 * rolled back to its pre-update state.
 *
 * Unlike {@see \App\Services\Core\CoreSourceSnapshot} we copy the whole
 * directory verbatim — extension repos do not embed user-specific data
 * inside their own tree (user data lives in core's `storage/` and DB).
 *
 * Snapshots are stored at:
 *   storage/app/private/extension-update/snapshots/{kind}/{slug}/{timestamp}/
 *
 * where `kind` is `plugin` or `theme`.
 */
class ExtensionSourceSnapshot
{
    public const KIND_PLUGIN = 'plugin';

    public const KIND_THEME = 'theme';

    /**
     * Capture the live directory to a fresh snapshot path.
     *
     * @param  string  $kind  ExtensionSourceSnapshot::KIND_PLUGIN or KIND_THEME
     * @param  string  $directoryName  Plugin/theme directory name (e.g. "DixlasePages")
     * @param  string  $livePath  Absolute path to the directory in the live tree
     * @return string Absolute path to the snapshot directory
     */
    public function capture(string $kind, string $directoryName, string $livePath): string
    {
        $this->assertKind($kind);

        if (! is_dir($livePath)) {
            throw new RuntimeException("Cannot snapshot {$kind} '{$directoryName}': live path does not exist: {$livePath}");
        }

        $snapshotPath = $this->buildSnapshotPath($kind, $directoryName);
        File::ensureDirectoryExists(dirname($snapshotPath));
        File::copyDirectory($livePath, $snapshotPath);

        return $snapshotPath;
    }

    /**
     * Restore the live directory from a previously captured snapshot.
     *
     * Replaces the live directory atomically (delete + copy). On the first
     * unrecoverable error this throws — the caller should then alert the
     * operator with the snapshot path so the recovery can be finished by
     * hand.
     */
    public function restore(string $snapshotPath, string $livePath): void
    {
        if (! is_dir($snapshotPath)) {
            throw new RuntimeException("Snapshot path does not exist: {$snapshotPath}");
        }

        if (is_dir($livePath)) {
            File::deleteDirectory($livePath);
        }
        File::ensureDirectoryExists(dirname($livePath));
        File::copyDirectory($snapshotPath, $livePath);
    }

    /**
     * Delete a snapshot directory and free disk space. Safe to call after a
     * successful upgrade — failures are silent.
     */
    public function discard(string $snapshotPath): void
    {
        if (is_dir($snapshotPath)) {
            File::deleteDirectory($snapshotPath);
        }
    }

    /**
     * Where snapshots for a kind/directory pair are stored on disk.
     */
    public function snapshotRootPath(string $kind, string $directoryName): string
    {
        $this->assertKind($kind);

        return storage_path("app/private/extension-update/snapshots/{$kind}/{$directoryName}");
    }

    /**
     * Compose a fresh, unique snapshot path inside the kind/dir root.
     */
    protected function buildSnapshotPath(string $kind, string $directoryName): string
    {
        return $this->snapshotRootPath($kind, $directoryName).'/'.now()->format('YmdHis_').uniqid();
    }

    protected function assertKind(string $kind): void
    {
        if (! in_array($kind, [self::KIND_PLUGIN, self::KIND_THEME], true)) {
            throw new RuntimeException("Unknown snapshot kind: {$kind} (expected plugin or theme)");
        }
    }
}
