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

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Persistent pre-update backups for extension (plugin/theme) updates, plus
 * the restore/list/prune helpers that dls:{theme,plugin}:rollback build on.
 *
 * Unlike the transient {@see \App\Services\Extension\ExtensionSourceSnapshot}
 * (discarded on a successful update), these backups are kept — one directory
 * per update, retained up to config('extension_backups.retention') — so an
 * operator can roll a bad update back with a single command long after it
 * landed. The backup is a verbatim deep copy of the live extension tree
 * INCLUDING the gitignored resources/assets prebuilt output, so a rollback
 * restores the exact asset state the site was serving with no npm run.
 *
 * Layout: storage/app/private/extension-backups/{kind}s/{directory}/{ts}/
 * (kind is "plugin" or "theme"). Aside copies of a rolled-back tree are
 * kept next to the backups as .pre-rollback-* so a rollback is itself
 * recoverable.
 */
trait TakesExtensionBackup
{
    /**
     * Root directory holding all backups for one extension.
     */
    protected function extensionBackupRoot(string $kind, string $directoryName): string
    {
        return storage_path("app/private/extension-backups/{$kind}s/{$directoryName}");
    }

    /**
     * Deep-copy the live extension tree into a fresh, timestamped backup
     * directory (resources/assets included). Returns the absolute path.
     */
    protected function takeExtensionBackup(string $kind, string $directoryName, string $livePath): string
    {
        if (! is_dir($livePath)) {
            throw new RuntimeException("Cannot back up {$kind} '{$directoryName}': live path does not exist: {$livePath}");
        }

        $root = $this->extensionBackupRoot($kind, $directoryName);
        $target = $root.'/'.$this->freshBackupTimestamp($root);
        File::ensureDirectoryExists(dirname($target));
        File::copyDirectory($livePath, $target);

        return $target;
    }

    /**
     * Backup directory names for this subject, oldest first. Aside copies
     * (.pre-rollback-*) and other dot-dirs are excluded.
     *
     * @return string[]
     */
    protected function listExtensionBackups(string $kind, string $directoryName): array
    {
        $root = $this->extensionBackupRoot($kind, $directoryName);
        if (! is_dir($root)) {
            return [];
        }

        $names = array_map('basename', File::directories($root));
        $names = array_values(array_filter($names, static fn (string $n): bool => ! str_starts_with($n, '.')));
        sort($names); // timestamp-prefixed names sort chronologically

        return $names;
    }

    /**
     * Absolute path of the newest backup, or null when none exist.
     */
    protected function latestExtensionBackupPath(string $kind, string $directoryName): ?string
    {
        $names = $this->listExtensionBackups($kind, $directoryName);
        if ($names === []) {
            return null;
        }

        return $this->extensionBackupRoot($kind, $directoryName).'/'.end($names);
    }

    /**
     * Resolve the backup to restore: an explicit timestamp when given (must
     * exist), otherwise the newest. Null when nothing matches.
     */
    protected function resolveExtensionBackupPath(string $kind, string $directoryName, ?string $timestamp): ?string
    {
        if ($timestamp !== null && $timestamp !== '') {
            $path = $this->extensionBackupRoot($kind, $directoryName).'/'.$timestamp;

            return is_dir($path) ? $path : null;
        }

        return $this->latestExtensionBackupPath($kind, $directoryName);
    }

    /**
     * Keep the newest $keep backups, delete older ones. Aside copies are
     * left untouched (recovery state, not part of the retained set).
     */
    protected function pruneExtensionBackups(string $kind, string $directoryName, int $keep): void
    {
        if ($keep < 1) {
            $keep = 1;
        }

        $names = $this->listExtensionBackups($kind, $directoryName);
        $root = $this->extensionBackupRoot($kind, $directoryName);
        $excess = array_slice($names, 0, max(0, count($names) - $keep));
        foreach ($excess as $old) {
            File::deleteDirectory($root.'/'.$old);
        }
    }

    /**
     * Restore a backup into the live path. The current live tree is moved
     * aside first (recoverable if the restore itself fails), and the backup
     * is copied — not consumed — so --to can be reused and the audit trail
     * stays stable. Returns the absolute path of the moved-aside tree.
     */
    protected function restoreExtensionBackupInto(string $backupPath, string $livePath): string
    {
        if (! is_dir($backupPath)) {
            throw new RuntimeException("Backup path does not exist: {$backupPath}");
        }

        $asidePath = dirname($backupPath).'/.pre-rollback-'.now()->format('Ymd-His').'-'.substr(md5(uniqid('', true)), 0, 6);

        if (is_dir($livePath)) {
            if (! @rename($livePath, $asidePath)) {
                File::ensureDirectoryExists($asidePath);
                File::copyDirectory($livePath, $asidePath);
                File::deleteDirectory($livePath);
            }
        }

        try {
            File::ensureDirectoryExists(dirname($livePath));
            File::copyDirectory($backupPath, $livePath);
        } catch (\Throwable $e) {
            // Recover: put the aside copy back so the operator is not left
            // with an empty extension directory.
            if (is_dir($asidePath)) {
                if (is_dir($livePath)) {
                    File::deleteDirectory($livePath);
                }
                if (! @rename($asidePath, $livePath)) {
                    File::copyDirectory($asidePath, $livePath);
                }
            }
            throw $e;
        }

        return $asidePath;
    }

    /**
     * A unique timestamped directory name inside $root: base "Ymd-His"; if
     * that already exists (two updates within one second), append -2, -3, …
     */
    private function freshBackupTimestamp(string $root): string
    {
        $base = now()->format('Ymd-His');
        $name = $base;
        $n = 1;
        while (is_dir($root.'/'.$name)) {
            $n++;
            $name = $base.'-'.$n;
        }

        return $name;
    }
}
