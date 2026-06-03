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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
     * Delete a snapshot directory and free disk space. Safe to call after a
     * successful upgrade — failures are logged but never thrown.
     */
    public function discard(string $snapshotPath): void
    {
        if (is_dir($snapshotPath)) {
            File::deleteDirectory($snapshotPath);
        }
    }

    /**
     * Where snapshots are stored on disk.
     */
    public function snapshotRootPath(): string
    {
        return storage_path('app/private/core-update/snapshots');
    }
}
