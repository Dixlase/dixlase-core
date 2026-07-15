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

namespace App\Services\Core;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Captures and restores a snapshot of core source files for safe upgrades.
 *
 * The snapshot covers only the directories the upgrader is allowed to
 * replace. Local data (`.env`, `storage/`, `vendor/`, `node_modules/`,
 * `plugins/`, `themes/`, `custom/`, `bootstrap/cache/`) is never copied so
 * snapshots stay small and a restore never clobbers user data.
 *
 * Snapshots are stored at:
 *   storage/app/private/core-update/snapshots/{timestamp}/
 *
 * Each entry under that directory is a verbatim copy of one source root
 * (e.g. `app/`, `config/`, `routes/`). Restore replaces the live root with
 * the snapshot copy.
 */
class CoreSourceSnapshot
{
    /**
     * Directories that are part of the core source tree and may be replaced
     * during an upgrade. A failure during extraction must restore exactly
     * these.
     *
     * @var list<string>
     */
    public const SOURCE_DIRECTORIES = [
        'app',
        'bootstrap',
        'config',
        'database/migrations',
        'database/seeders',
        'lang',
        'public',
        'resources',
        'routes',
    ];

    /**
     * Top-level files that may be replaced. `bootstrap/cache/` is excluded
     * via the directory list (we copy `bootstrap/` whole, but cache is
     * cleared after restore anyway).
     *
     * @var list<string>
     */
    public const SOURCE_FILES = [
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'vite.config.js',
        'postcss.config.js',
    ];

    /**
     * Directories that must NEVER be touched by an upgrade.
     *
     * @var list<string>
     */
    public const PROTECTED_PATHS = [
        '.env',
        '.env.example',
        'storage',
        'vendor',
        'node_modules',
        'plugins',
        'themes',
        'custom',
        'bootstrap/cache',
        'database/migration-lock.json',
    ];

    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Capture the source tree to a fresh snapshot directory.
     * Returns the absolute path to the snapshot root.
     */
    public function capture(): string
    {
        $snapshotPath = $this->snapshotRootPath().'/'.now()->format('YmdHis_').uniqid();
        File::ensureDirectoryExists($snapshotPath);

        foreach (self::SOURCE_DIRECTORIES as $relative) {
            $source = $this->basePath.'/'.$relative;
            if (! is_dir($source)) {
                continue;
            }
            $target = $snapshotPath.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));
            self::copyDirectoryWithoutSymlinks($source, $target);
        }

        foreach (self::SOURCE_FILES as $relative) {
            $source = $this->basePath.'/'.$relative;
            if (! is_file($source)) {
                continue;
            }
            $target = $snapshotPath.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
        }

        return $snapshotPath;
    }

    /**
     * Restore the live tree from a previously captured snapshot.
     *
     * Each source root is replaced atomically (delete + copy). On the first
     * unrecoverable error this throws — the caller should then alert the
     * operator with the snapshot path so they can finish the restore by
     * hand.
     */
    public function restore(string $snapshotPath): void
    {
        if (! is_dir($snapshotPath)) {
            throw new RuntimeException("Snapshot path does not exist: {$snapshotPath}");
        }

        foreach (self::SOURCE_DIRECTORIES as $relative) {
            $snapshotEntry = $snapshotPath.'/'.$relative;
            if (! is_dir($snapshotEntry)) {
                continue;
            }
            $live = $this->basePath.'/'.$relative;
            File::ensureDirectoryExists(dirname($live));
            $this->replaceLiveDirectory($snapshotEntry, $live);
        }

        foreach (self::SOURCE_FILES as $relative) {
            $snapshotEntry = $snapshotPath.'/'.$relative;
            if (! is_file($snapshotEntry)) {
                continue;
            }
            $live = $this->basePath.'/'.$relative;
            if (is_file($live)) {
                File::delete($live);
            }
            File::ensureDirectoryExists(dirname($live));
            File::copy($snapshotEntry, $live);
        }

        // File::copy preserves contents but not the executable bit, so a
        // restored `artisan` comes back non-executable and `./artisan` stops
        // working. Restore its canonical mode explicitly.
        $liveArtisan = $this->basePath.'/artisan';
        if (is_file($liveArtisan)) {
            @chmod($liveArtisan, 0755);
        }
    }

    /**
     * Replace the contents of $live with $source, preserving every
     * symlink that exists in the live tree.
     *
     * Symlink-aware analogue of `File::deleteDirectory($live);
     * File::copyDirectory($source, $live);` — same effect for regular
     * files and directories, but every symlink in the live tree is
     * skipped on both the delete side (so it survives the swap) and the
     * copy side (so the source's symlinks, if any, are not materialised
     * into the live tree). Both the snapshot rollback path and
     * CoreUpdater::applyToLiveTree() route their directory swaps
     * through here so the runtime-created symlinks under public/ —
     * public/storage (from `php artisan storage:link`) and
     * public/assets/themes/<slug> (from `dls:theme:symlink`) — survive
     * both a successful upgrade and a failed-then-rolled-back upgrade.
     */
    public function replaceLiveDirectory(string $source, string $live): void
    {
        self::removeContentPreservingSymlinks($live);
        File::ensureDirectoryExists($live);
        self::copyDirectoryWithoutSymlinks($source, $live);
    }

    /**
     * Recursive copy from $from to $to that skips every symlink.
     *
     * Symlinks under the core source tree always point at protected
     * paths outside the snapshot's scope (public/storage →
     * storage/app/public, public/assets/themes/<slug> →
     * themes/<slug>/resources/assets). Following them with PHP's
     * `copy()` either fails on a dangling target (e.g. before
     * storage:link's target dir exists on a fresh install) or pollutes
     * the snapshot with user data that would shadow the real symlinks
     * on restore. Skipping them entirely keeps snapshots small, side-
     * effect-free, and tolerant of dangling-symlink installs.
     */
    protected static function copyDirectoryWithoutSymlinks(string $from, string $to): void
    {
        if (! is_dir($from)) {
            return;
        }
        File::ensureDirectoryExists($to);

        $iter = new \FilesystemIterator($from, \FilesystemIterator::SKIP_DOTS);
        foreach ($iter as $item) {
            $path = $item->getPathname();
            $dest = $to.'/'.$item->getBasename();
            if (is_link($path)) {
                continue;
            }
            if (is_dir($path)) {
                self::copyDirectoryWithoutSymlinks($path, $dest);
            } elseif (is_file($path)) {
                copy($path, $dest);
            }
        }
    }

    /**
     * Recursively remove the contents of $dir while leaving every
     * symlink in place (and the minimal directory scaffolding required
     * to hold them).
     *
     * A directory that contained only symlinks before this call still
     * contains only those symlinks afterwards — no `rmdir` is attempted
     * on it. A directory that becomes empty after its non-symlink
     * children are removed is itself removed.
     */
    protected static function removeContentPreservingSymlinks(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iter = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        foreach ($iter as $item) {
            $path = $item->getPathname();
            if (is_link($path)) {
                continue;
            }
            if (is_dir($path)) {
                self::removeContentPreservingSymlinks($path);
                if (self::isEmptyDir($path)) {
                    @rmdir($path);
                }
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }
    }

    protected static function isEmptyDir(string $dir): bool
    {
        $iter = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);

        return ! $iter->valid();
    }

    /**
     * Delete a snapshot directory (and its metadata sidecar) to free disk
     * space. Safe to call after a successful upgrade or rollback — failures
     * are logged but never thrown.
     */
    public function discard(string $snapshotPath): void
    {
        if (is_dir($snapshotPath)) {
            File::deleteDirectory($snapshotPath);
        }
        $sidecar = $this->metadataPath($snapshotPath);
        if (is_file($sidecar)) {
            @unlink($sidecar);
        }
    }

    /**
     * Where snapshots are stored on disk.
     */
    public function snapshotRootPath(): string
    {
        return storage_path('app/private/core-update/snapshots');
    }

    /**
     * Absolute path of the rollback-metadata sidecar for a snapshot dir.
     *
     * The sidecar sits NEXT TO the snapshot directory (not inside it) so
     * restore()'s per-root copy never drags it into the live source tree,
     * and listSnapshots() (which enumerates directories) never surfaces it
     * as a false snapshot. Mirrors the `.meta.json` convention that
     * {@see \App\Console\Traits\TakesExtensionBackup} uses for plugin/theme
     * rollbacks.
     */
    public function metadataPath(string $snapshotPath): string
    {
        return $snapshotPath.'.meta.json';
    }

    /**
     * Write the rollback-metadata sidecar for a snapshot. Records the from/to
     * versions, the DB backup record, and the pre-update migration batch so
     * dls:core:rollback can reverse exactly one update (source + optional
     * vendor + only that update's schema) without re-deriving anything by
     * heuristic.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function writeMetadata(string $snapshotPath, array $metadata): void
    {
        File::put(
            $this->metadataPath($snapshotPath),
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );
    }

    /**
     * Read a snapshot's rollback metadata, or null when the sidecar is
     * missing (a snapshot captured before this metadata was written, or a
     * bare pre-rollback safety snapshot) or unreadable / malformed. Callers
     * must treat null as "unknown state" and degrade to a source-only
     * rollback, not as an error.
     *
     * @return array<string, mixed>|null
     */
    public function readMetadata(string $snapshotPath): ?array
    {
        $sidecar = $this->metadataPath($snapshotPath);
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
     * All snapshot directories, newest first. Sidecar files and any other
     * non-directory entries are excluded.
     *
     * @return list<string> absolute paths
     */
    public function listSnapshots(): array
    {
        $root = $this->snapshotRootPath();
        if (! is_dir($root)) {
            return [];
        }

        $dirs = File::directories($root);
        // Names are "YmdHis_"-prefixed, so a reverse sort is newest-first.
        rsort($dirs);

        return array_values($dirs);
    }

    /**
     * The newest snapshot that carries rollback metadata — i.e. the most
     * recent recorded core update, the point dls:core:rollback winds back
     * to. Bare safety snapshots (no sidecar) are skipped. Null when no
     * rollback point exists.
     */
    public function latestSnapshotWithMetadata(): ?string
    {
        foreach ($this->listSnapshots() as $dir) {
            if (is_file($this->metadataPath($dir))) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Keep the newest $keep snapshots (each with its metadata sidecar) and
     * delete the older ones. Called after a successful update so the retained
     * rollback history stays bounded — each snapshot is a full copy of the
     * core source tree. The newest snapshot (this update's rollback point) is
     * always kept; pre-rollback safety snapshots count toward the total and
     * are pruned by age like any other. Best-effort; never throws.
     */
    public function pruneSnapshots(int $keep): void
    {
        if ($keep < 1) {
            $keep = 1;
        }

        // listSnapshots() is newest-first, so everything past the first $keep
        // is older than the retained window.
        foreach (array_slice($this->listSnapshots(), $keep) as $old) {
            $this->discard($old);
        }
    }
}
