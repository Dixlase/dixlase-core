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

namespace App\Console\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
     * directory (resources/assets included) and, when given, write a
     * `<timestamp>.meta.json` sidecar next to the backup dir. Returns the
     * absolute path of the backup directory (not the sidecar).
     *
     * The sidecar is a sibling of the backup directory, not a file inside
     * it, so restoreExtensionBackupInto()'s File::copyDirectory can never
     * drag the metadata into the live extension tree. dls:{plugin,theme}:
     * rollback reads it back via readBackupMetadata().
     *
     * @param  array<string, mixed>  $metadata  Optional snapshot of pre-backup
     *                                          state — currently `version` (declared plugin.json/theme.json version)
     *                                          and `max_batch` (highest applied migration batch), used by rollback
     *                                          to compute an exact --step for the schema half.
     */
    protected function takeExtensionBackup(string $kind, string $directoryName, string $livePath, array $metadata = []): string
    {
        if (! is_dir($livePath)) {
            throw new RuntimeException("Cannot back up {$kind} '{$directoryName}': live path does not exist: {$livePath}");
        }

        $root = $this->extensionBackupRoot($kind, $directoryName);
        $target = $root.'/'.$this->freshBackupTimestamp($root);
        File::ensureDirectoryExists(dirname($target));
        File::copyDirectory($livePath, $target);

        if ($metadata !== []) {
            $this->writeBackupMetadata($target, $metadata);
        }

        return $target;
    }

    /**
     * Absolute path of the metadata sidecar file for a given backup dir.
     * Sidecar sits next to the backup dir so File::copyDirectory in
     * restoreExtensionBackupInto never drags it into the live tree, and
     * listExtensionBackups (which enumerates DIRECTORIES) never surfaces
     * it as a false-positive backup.
     */
    protected function extensionBackupMetadataPath(string $backupPath): string
    {
        return $backupPath.'.meta.json';
    }

    /**
     * Read the metadata sidecar for a backup. Returns null when the sidecar
     * is missing (pre-fix backups taken before this file wrote metadata) or
     * unreadable / malformed — callers must treat null as "unknown state"
     * and fall back to the pre-fix delegated behaviour, not as an error.
     *
     * @return array<string, mixed>|null
     */
    protected function readBackupMetadata(string $backupPath): ?array
    {
        $sidecar = $this->extensionBackupMetadataPath($backupPath);
        if (! is_file($sidecar)) {
            return null;
        }

        $contents = @file_get_contents($sidecar);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function writeBackupMetadata(string $backupPath, array $metadata): void
    {
        // Timestamp is folded into the payload so an operator inspecting the
        // sidecar in isolation can still see when the backup was captured.
        $metadata = array_merge(['timestamp' => basename($backupPath)], $metadata);
        File::put(
            $this->extensionBackupMetadataPath($backupPath),
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                .PHP_EOL,
        );
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
     * Keep the newest $keep backups, delete older ones AND their metadata
     * sidecars. Aside copies (.pre-rollback-*) are left untouched (recovery
     * state, not part of the retained set).
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
            $backupPath = $root.'/'.$old;
            File::deleteDirectory($backupPath);
            $sidecar = $this->extensionBackupMetadataPath($backupPath);
            if (is_file($sidecar)) {
                @unlink($sidecar);
            }
        }
    }

    /**
     * Delete a single backup dir AND its metadata sidecar. Used to consume
     * the restore point after a successful rollback (mirrors dls:core:rollback,
     * which discards the snapshot it restores from) so the "rollback available"
     * affordance clears once the operator has stepped back through it. Aside
     * copies (.pre-rollback-*) are left untouched — they are the undo path for
     * the rollback itself, not part of the retained backup set.
     */
    protected function discardExtensionBackup(string $backupPath): void
    {
        if (is_dir($backupPath)) {
            File::deleteDirectory($backupPath);

            // A silent failure here (e.g. a permission problem on a file the
            // current process does not own) would leave the restore point on
            // disk and the admin rollback button stuck on with no explanation.
            // clearstatcache so the re-check reflects the deletion just made.
            clearstatcache(true, $backupPath);
            if (is_dir($backupPath)) {
                Log::warning('Extension backup consume failed: directory still present after delete', [
                    'backup_path' => $backupPath,
                    'owner' => function_exists('fileowner') ? @fileowner($backupPath) : null,
                    'process_uid' => function_exists('posix_geteuid') ? @posix_geteuid() : null,
                ]);
            }
        }
        $sidecar = $this->extensionBackupMetadataPath($backupPath);
        if (is_file($sidecar)) {
            @unlink($sidecar);
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
