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
            File::copyDirectory($source, $target);
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
            if (is_dir($live)) {
                File::deleteDirectory($live);
            }
            File::ensureDirectoryExists(dirname($live));
            File::copyDirectory($snapshotEntry, $live);
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
