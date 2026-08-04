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
        // Committed at the repo root; carries the semver-ish on-disk
        // version that CoreUpdater::readVersionFromDisk() feeds to the
        // downgrade guard (Finding #1) and VersionDriftService compares
        // against the ledger (Finding #5). Must be captured in the
        // snapshot AND copied by applyToLiveTree(); without this entry,
        // an update overwrites live source but never writes the release's
        // VERSION → drift service permanently reports "unknown" post-
        // update and the on-disk guard never fires. Sandbox verification
        // 2026-07-31 (issue #171 Finding A) confirmed the omission.
        'VERSION',
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
     * Replace the contents of $live with $source atomically.
     *
     * Round 5 PR-Q: the pre-atomic implementation was a
     * `removeContentPreservingSymlinks($live)` followed by a fresh
     * `copyDirectoryWithoutSymlinks($source, $live)` — a delete-then-
     * copy that took seconds on slow disks and exposed the live tree
     * to any web request landing in the window. Even with the
     * Round 4/5 maintenance middleware + FPM stat-cache bypass, the
     * sandbox dryrun-13/14 verification observed real fatals from
     * requests that slipped through:
     * `include(app/Enums/MenuVisibility.php): No such file` on the
     * update side, and `include(GuardAwareDatabaseSessionHandler.php)`
     * on the rollback side. Any request touching a not-yet-recopied
     * class would fatal.
     *
     * The rename-based implementation makes the swap atomic at the
     * kernel level: the staged tree is fully assembled at `$live.new`,
     * the current live tree is renamed to `$live.old`, and the staged
     * tree is renamed into `$live`. Every request either sees the
     * pre-swap tree in full or the post-swap tree in full — never a
     * half-swapped state — regardless of maintenance-mode timing.
     *
     * Two things must survive the swap that were previously handled
     * by the "preserving symlinks" naming of the old method:
     *
     *   1. Runtime-created symlinks under public/ — public/storage
     *      (from `php artisan storage:link`), public/assets/themes/
     *      <slug> and public/assets/plugins/<slug> (from
     *      `dls:theme:symlink` / `dls:plugin:symlink`), public/assets/
     *      admin (from `make:link-assets`). They live in the live tree
     *      but never in the staged tree, so they would be lost when
     *      the staged tree becomes the new live tree. Before the
     *      rename we scan the live tree for these and recreate them
     *      in $live.new at the same relative paths with the same
     *      targets.
     *   2. Protected children inside a swapped source dir — most
     *      importantly `bootstrap/cache/`. It is listed in
     *      PROTECTED_PATHS but sits INSIDE the `bootstrap` directory
     *      that gets swapped. Before the rename we move the contents
     *      of any such protected child from live into $live.new.
     *
     * On a filesystem that cannot rename between $live and $live.new
     * (e.g. `$live` is on a different mount than the staging root),
     * the method falls back to the pre-atomic behaviour with a log
     * line — imperfect but preserves correctness across setups where
     * the atomic swap cannot apply.
     */
    public function replaceLiveDirectory(string $source, string $live): void
    {
        $newDir = $live.'.new';
        $oldDir = $live.'.old';

        // Recover any leftover directories from an interrupted prior
        // run. The atomic swap depends on these paths being available.
        if (is_dir($newDir)) {
            File::deleteDirectory($newDir);
        }
        if (is_dir($oldDir)) {
            File::deleteDirectory($oldDir);
        }

        // Stage the staged tree into $live.new. `copyDirectoryWithoutSymlinks`
        // is inherited from the pre-atomic implementation: staged trees
        // (from snapshot dirs or a downloaded release payload) never
        // contain symlinks by design, so no symlink handling is needed
        // on this side.
        File::ensureDirectoryExists($newDir);
        self::copyDirectoryWithoutSymlinks($source, $newDir);

        // If the live tree exists, salvage the state that must survive
        // the swap: runtime symlinks and protected children.
        if (is_dir($live)) {
            self::recreateLiveSymlinksInto($live, $newDir);
            self::moveProtectedChildrenInto($live, $newDir);
        }

        // Atomic swap. Both renames are metadata-only kernel operations
        // and complete in microseconds. The window between them (during
        // which `$live` momentarily points at an inode belonging to
        // `$oldDir`) is not observable by web requests — the directory
        // is briefly named `$live.old` rather than absent.
        if (is_dir($live)) {
            if (! @rename($live, $oldDir)) {
                // Cross-filesystem or permission failure. Fall back to
                // the pre-atomic behaviour rather than aborting; the
                // caller (and the operator) still get a working tree.
                self::deleteRecursive($newDir);
                self::removeContentPreservingSymlinks($live);
                File::ensureDirectoryExists($live);
                self::copyDirectoryWithoutSymlinks($source, $live);

                return;
            }
        }

        if (! @rename($newDir, $live)) {
            // Best-effort recovery: put the old dir back where it was.
            if (is_dir($oldDir)) {
                @rename($oldDir, $live);
            }
            throw new RuntimeException(
                "Atomic swap failed: could not rename {$newDir} into {$live}. "
                .'The pre-swap tree has been restored; the update or rollback '
                .'is aborted safely.'
            );
        }

        // Cleanup the pre-swap tree. Failure here does not affect the
        // just-completed swap.
        if (is_dir($oldDir)) {
            self::deleteRecursive($oldDir);
        }
    }

    /**
     * Scan $liveDir for symlinks (recursively, without descending
     * into them) and recreate each one at the equivalent relative
     * path under $targetDir. Called before the atomic rename so the
     * runtime-created symlinks under public/ survive into the new
     * live tree. Best-effort: a single-symlink failure is logged and
     * swallowed rather than aborting the whole swap.
     */
    protected static function recreateLiveSymlinksInto(string $liveDir, string $targetDir): void
    {
        $links = self::collectSymlinks($liveDir);
        foreach ($links as $relative => $target) {
            $destination = $targetDir.'/'.$relative;
            File::ensureDirectoryExists(dirname($destination));
            if (is_link($destination) || file_exists($destination)) {
                // A staged file already occupies the slot — drop it so
                // the symlink can take its place. The staged content
                // was a shadow of what the runtime symlink points at.
                @unlink($destination);
            }
            @symlink($target, $destination);
        }
    }

    /**
     * @return array<string, string> Symlinks under $dir keyed by
     *                               path relative to $dir; value is the symlink's own target
     *                               (raw readlink, may be relative).
     */
    protected static function collectSymlinks(string $dir): array
    {
        $collected = [];
        if (! is_dir($dir)) {
            return $collected;
        }

        self::walkForSymlinks($dir, '', $collected);

        return $collected;
    }

    /**
     * Depth-first walk that adds symlinks to $collected and descends
     * into real subdirectories only — never into a symlinked
     * subdirectory, so `public/storage` is captured as one entry
     * without walking the storage/ tree behind it.
     *
     * @param  array<string, string>  &$collected
     */
    protected static function walkForSymlinks(string $baseDir, string $relative, array &$collected): void
    {
        $current = $relative === '' ? $baseDir : $baseDir.'/'.$relative;
        $iter = new \FilesystemIterator($current, \FilesystemIterator::SKIP_DOTS);
        foreach ($iter as $item) {
            $path = $item->getPathname();
            $childRelative = $relative === ''
                ? $item->getBasename()
                : $relative.'/'.$item->getBasename();

            if (is_link($path)) {
                $target = @readlink($path);
                if ($target !== false) {
                    $collected[$childRelative] = $target;
                }

                continue;
            }
            if (is_dir($path)) {
                self::walkForSymlinks($baseDir, $childRelative, $collected);
            }
        }
    }

    /**
     * Move each PROTECTED_PATHS entry that lives INSIDE $liveDir
     * (e.g. `bootstrap/cache/` under a bootstrap swap) into
     * $newDir at the same relative path. Called before the atomic
     * rename so cache contents survive the swap.
     */
    protected static function moveProtectedChildrenInto(string $liveDir, string $newDir): void
    {
        // liveDir is an absolute path like `/var/www/html/bootstrap`.
        // We need the relative-from-base — the caller doesn't tell us
        // the base, so derive it from PROTECTED_PATHS entries that
        // could be a child of this dir.
        foreach (self::PROTECTED_PATHS as $protectedRelativeToBase) {
            $liveDirBasename = basename($liveDir);
            $expectedPrefix = $liveDirBasename.'/';
            if (! str_starts_with($protectedRelativeToBase, $expectedPrefix)) {
                continue;
            }
            $childRelative = substr($protectedRelativeToBase, strlen($expectedPrefix));
            $liveChild = $liveDir.'/'.$childRelative;
            if (! file_exists($liveChild) && ! is_link($liveChild)) {
                continue;
            }
            $newChild = $newDir.'/'.$childRelative;
            File::ensureDirectoryExists(dirname($newChild));
            if (file_exists($newChild) || is_link($newChild)) {
                self::deleteRecursive($newChild);
            }
            @rename($liveChild, $newChild);
        }
    }

    /**
     * Best-effort recursive delete used by the atomic-swap paths.
     * `File::deleteDirectory` on a directory that contains a symlink
     * behaves fine, but this wrapper also handles a leaf file/symlink
     * so callers do not have to type-check.
     */
    protected static function deleteRecursive(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
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
     * The newest snapshot that carries rollback metadata AND records a
     * from-version the rollback machinery can actually resolve — i.e.
     * the most recent recorded core update the operator can safely wind
     * back to. Bare safety snapshots (no sidecar) and snapshots whose
     * from-version is unresolvable (missing / empty / `0.0.0`) are
     * skipped so a stale placeholder cannot become the offered rollback
     * point once a legitimate one is consumed. See Round 4 Finding B.
     */
    public function latestSnapshotWithMetadata(): ?string
    {
        foreach ($this->listSnapshots() as $dir) {
            if (! is_file($this->metadataPath($dir))) {
                continue;
            }
            if (! self::hasResolvableFromVersion($this->readMetadata($dir))) {
                continue;
            }

            return $dir;
        }

        return null;
    }

    /**
     * True when the given metadata sidecar records a from-version the
     * updater/rollback machinery can actually resolve — a non-empty
     * string that is not the `0.0.0` placeholder. The placeholder is
     * what pre-baseline installs wrote into meta when the ledger was
     * empty (fixed forward by the install-time baseline row added in
     * issue #171 Finding D / PR #174); no such GitHub release exists to
     * fetch vendor/ from, so offering such a snapshot as a rollback
     * point traps the operator in a destructive fail.
     *
     * Static so callers that already hold the decoded sidecar (UI-side
     * `buildCoreSection`, the rollback command's own guard) can share
     * the same predicate without a second file read.
     *
     * @param  array<string,mixed>|null  $meta
     */
    public static function hasResolvableFromVersion(?array $meta): bool
    {
        if ($meta === null) {
            return false;
        }

        $from = $meta['from'] ?? null;

        return is_string($from) && $from !== '' && $from !== '0.0.0';
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

    /**
     * Delete any snapshot older than the newest that carries an
     * unresolvable-from sidecar (missing / empty / `0.0.0`). Called
     * after a successful update so a stale `from:0.0.0` snapshot left
     * over from a pre-baseline update cannot become the offered
     * rollback point once the current, resolvable snapshot is
     * consumed. Count-based prune (see {@see pruneSnapshots}) handles
     * bounding by age; this method separately handles unresolvability.
     *
     * The newest snapshot is preserved regardless of its from-version:
     * even if it is itself unresolvable it may be the operator's sole
     * artifact of a just-applied update, and deleting it silently
     * would remove that record with no warning. Best-effort; never
     * throws.
     */
    public function pruneUnresolvableSnapshots(): void
    {
        $all = $this->listSnapshots();
        if (count($all) <= 1) {
            return;
        }

        // Skip the newest (index 0). Everything older with an
        // unresolvable-from sidecar can be dropped.
        foreach (array_slice($all, 1) as $older) {
            if (! is_file($this->metadataPath($older))) {
                // Bare (no-sidecar) snapshots are pre-rollback safety
                // captures and are already unreachable as rollback
                // points; leave them for the count-based prune.
                continue;
            }
            if (self::hasResolvableFromVersion($this->readMetadata($older))) {
                continue;
            }
            $this->discard($older);
        }
    }
}
