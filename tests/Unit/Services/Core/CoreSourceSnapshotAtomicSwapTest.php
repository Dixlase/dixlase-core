<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace Tests\Unit\Services\Core;

use App\Services\Core\CoreSourceSnapshot;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pins the Round 5 PR-Q atomic swap implementation of
 * `CoreSourceSnapshot::replaceLiveDirectory()`.
 *
 * The pre-atomic implementation (`removeContentPreservingSymlinks`
 * followed by `copyDirectoryWithoutSymlinks`) was a delete-then-copy
 * that took seconds on slow disks and exposed the live tree to any
 * web request landing in the window. The sandbox dryrun-13/14
 * verification observed real `include(...) No such file` fatals from
 * requests that slipped through, even with the Round 4/5 maintenance
 * middleware + FPM stat-cache bypass. The rename-based implementation
 * removes the window entirely: every reader sees the pre-swap tree
 * in full or the post-swap tree in full.
 *
 * These tests exercise the swap end-to-end against a throw-away
 * directory tree — no touching of the real project root — and pin
 * the invariants that must hold across future refactors:
 *   - Regular files are replaced correctly.
 *   - Runtime symlinks in the live tree survive into the new live
 *     tree (public/storage, public/assets/themes/<slug>, ...).
 *   - PROTECTED_PATHS entries that live INSIDE a swapped source dir
 *     (bootstrap/cache) survive by being moved into the staging dir
 *     before the rename.
 *   - Leftover `.new` / `.old` from a prior interrupted run are
 *     recovered.
 */
class CoreSourceSnapshotAtomicSwapTest extends TestCase
{
    private string $workDir;

    private CoreSourceSnapshot $snapshotter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-atomic-swap-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
        $this->snapshotter = new CoreSourceSnapshot($this->workDir);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_swap_replaces_regular_files(): void
    {
        [$source, $live] = $this->makePair();
        $this->writeFile($live.'/foo.php', 'OLD');
        $this->writeFile($source.'/foo.php', 'NEW');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertSame('NEW', (string) file_get_contents($live.'/foo.php'));
    }

    public function test_swap_removes_files_absent_from_source(): void
    {
        [$source, $live] = $this->makePair();
        $this->writeFile($live.'/stale.php', 'STALE');
        $this->writeFile($source.'/kept.php', 'KEPT');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertFileDoesNotExist($live.'/stale.php',
            'A file that exists only in the live tree must be gone after the swap.');
        $this->assertFileExists($live.'/kept.php');
    }

    public function test_swap_preserves_top_level_symlinks_under_live(): void
    {
        // public/storage → ../storage/app/public pattern — a symlink
        // at the root of the swapped dir.
        [$source, $live] = $this->makePair();
        $target = $this->workDir.'/external-storage';
        mkdir($target, 0755, true);
        $this->writeFile($target.'/marker.txt', 'external');
        symlink($target, $live.'/storage');
        $this->writeFile($source.'/app.php', 'source app');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertTrue(is_link($live.'/storage'),
            'A symlink at the root of the swapped dir must survive the rename swap.');
        $this->assertSame(
            $target,
            readlink($live.'/storage'),
            'The symlink target must be preserved verbatim (no resolve, no rewrite).'
        );
        $this->assertSame('external', (string) file_get_contents($live.'/storage/marker.txt'),
            'The preserved symlink must still resolve to the same external location.');
    }

    public function test_swap_preserves_nested_symlinks_under_live(): void
    {
        // public/assets/themes/DixlaseOnePage → ../../../themes/... pattern —
        // a symlink one level deep. The walker must not confuse it for
        // a directory and try to descend into it.
        [$source, $live] = $this->makePair();
        $themeTarget = $this->workDir.'/external-theme';
        mkdir($themeTarget, 0755, true);
        $this->writeFile($themeTarget.'/style.css', 'external theme');
        mkdir($live.'/assets/themes', 0755, true);
        symlink($themeTarget, $live.'/assets/themes/DixlaseOnePage');
        $this->writeFile($source.'/assets/manifest.json', '{"version":2}');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertTrue(is_link($live.'/assets/themes/DixlaseOnePage'),
            'A symlink nested inside the swapped dir must survive.');
        $this->assertSame('external theme', (string) file_get_contents($live.'/assets/themes/DixlaseOnePage/style.css'));
        $this->assertSame('{"version":2}', (string) file_get_contents($live.'/assets/manifest.json'));
    }

    public function test_swap_clears_a_staged_real_directory_so_the_live_symlink_survives(): void
    {
        // A release ZIP that dereferenced public/assets/themes/<Theme> ships it
        // as a real directory, so the staged tree occupies the slot the live
        // symlink needs. unlink() cannot remove a directory, so without a
        // recursive delete the link is never recreated and the live tree ends
        // up holding a stale copy — which a later rollback then deletes.
        [$source, $live] = $this->makePair();
        $themeTarget = $this->workDir.'/external-theme';
        mkdir($themeTarget, 0755, true);
        $this->writeFile($themeTarget.'/style.css', 'external theme');
        mkdir($live.'/assets/themes', 0755, true);
        symlink($themeTarget, $live.'/assets/themes/DixlaseOnePage');
        $this->writeFile($source.'/assets/themes/DixlaseOnePage/style.css', 'dereferenced copy from the zip');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertTrue(
            is_link($live.'/assets/themes/DixlaseOnePage'),
            'The live symlink must survive a staged payload that holds a real directory at the same path.'
        );
        $this->assertSame($themeTarget, readlink($live.'/assets/themes/DixlaseOnePage'));
        $this->assertSame(
            'external theme',
            (string) file_get_contents($live.'/assets/themes/DixlaseOnePage/style.css'),
            'The link must resolve to the extension assets, not the dereferenced copy.'
        );
    }

    public function test_swap_preserves_bootstrap_cache_when_swapping_bootstrap(): void
    {
        // `bootstrap/cache` is in PROTECTED_PATHS but lives INSIDE the
        // `bootstrap` dir that gets swapped. It must be moved into the
        // staging dir before the rename so its contents survive.
        [$source, $live] = $this->makePair('bootstrap');
        mkdir($live.'/cache', 0755, true);
        $this->writeFile($live.'/cache/services.php', 'compiled services cache');
        $this->writeFile($source.'/providers.php', 'new providers list');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertFileExists($live.'/cache/services.php',
            'bootstrap/cache/services.php must survive the bootstrap swap.');
        $this->assertSame('compiled services cache', (string) file_get_contents($live.'/cache/services.php'));
        $this->assertFileExists($live.'/providers.php');
    }

    public function test_swap_recovers_from_leftover_new_and_old_dirs(): void
    {
        // Simulate a crash mid-swap: `.new` and `.old` sibling dirs
        // are lying around from a previous run. The swap must clean
        // them up and complete successfully rather than throwing.
        [$source, $live] = $this->makePair();
        $this->writeFile($live.'/foo.php', 'CURRENT');
        $this->writeFile($source.'/foo.php', 'NEW');
        mkdir($live.'.new', 0755, true);
        $this->writeFile($live.'.new/stale-partial-swap.php', 'GHOST');
        mkdir($live.'.old', 0755, true);
        $this->writeFile($live.'.old/orphaned.php', 'GHOST');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertSame('NEW', (string) file_get_contents($live.'/foo.php'));
        $this->assertDirectoryDoesNotExist($live.'.new',
            'Leftover .new from a crashed prior run must be cleaned up.');
        $this->assertDirectoryDoesNotExist($live.'.old',
            'Leftover .old from a crashed prior run must be cleaned up.');
    }

    public function test_swap_source_symlink_is_not_materialised_in_live(): void
    {
        // Staged trees (snapshot dirs, release payloads) are supposed
        // to be symlink-free. If a symlink somehow appears in $source,
        // it must NOT be copied over into $live — the pre-atomic
        // implementation was explicit about this and the atomic
        // implementation must preserve that contract.
        [$source, $live] = $this->makePair();
        $extern = $this->workDir.'/extern';
        mkdir($extern, 0755, true);
        $this->writeFile($extern.'/leak.txt', 'external');
        symlink($extern, $source.'/leaked');
        $this->writeFile($source.'/legit.php', 'ok');

        $this->snapshotter->replaceLiveDirectory($source, $live);

        $this->assertFileDoesNotExist($live.'/leaked',
            'Symlinks in the source (staged) tree must NEVER be materialised '
            .'into the live tree — they would either dangle or shadow real '
            .'runtime symlinks the operator created intentionally.');
        $this->assertFileExists($live.'/legit.php');
    }

    /**
     * Create a fresh (source, live) pair inside the workdir and return
     * their absolute paths. `$liveBasename` controls the live-dir name
     * — the swap logic reads PROTECTED_PATHS entries by dir basename,
     * so tests that exercise bootstrap/cache preservation must use
     * `'bootstrap'` as the basename.
     *
     * @return array{0: string, 1: string}
     */
    private function makePair(string $liveBasename = 'app'): array
    {
        $source = $this->workDir.'/source-'.uniqid();
        $live = $this->workDir.'/'.$liveBasename;
        mkdir($source, 0755, true);
        mkdir($live, 0755, true);

        return [$source, $live];
    }

    private function writeFile(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $contents);
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                @unlink($file->getPathname());
            } elseif ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($path);
    }
}
