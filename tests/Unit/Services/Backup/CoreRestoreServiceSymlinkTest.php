<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\CoreRestoreService;
use Tests\TestCase;

/**
 * Restore must never follow symlinks while clearing directories.
 *
 * public/ contains the public/storage -> storage/app/public link, so
 * a restore of the core source tree that recursed through symlinks
 * would wipe the media library — data entirely outside the restore
 * target. These tests pin the symlink-safe behaviour of
 * clearDirectory() / removeDirectory().
 */
class CoreRestoreServiceSymlinkTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/restore-symlink-test-'.getmypid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_clear_directory_preserves_symlink_and_its_target_contents(): void
    {
        // outside/ stands in for storage/app/public; cleared/ for public/
        $outside = $this->workDir.'/outside';
        $cleared = $this->workDir.'/cleared';
        mkdir($outside, 0755, true);
        mkdir($cleared, 0755, true);
        file_put_contents($outside.'/media.txt', 'precious');
        file_put_contents($cleared.'/regular.txt', 'replace me');
        symlink($outside, $cleared.'/storage');

        $this->invokeClearDirectory($cleared);

        $this->assertFileExists($outside.'/media.txt', 'clearDirectory must not delete files through a symlink');
        $this->assertTrue(is_link($cleared.'/storage'), 'clearDirectory must leave the symlink itself in place');
        $this->assertFileDoesNotExist($cleared.'/regular.txt', 'regular files must still be cleared');
    }

    public function test_remove_directory_unlinks_nested_symlink_without_following_it(): void
    {
        $outside = $this->workDir.'/outside';
        $doomed = $this->workDir.'/doomed';
        mkdir($outside, 0755, true);
        mkdir($doomed.'/nested', 0755, true);
        file_put_contents($outside.'/media.txt', 'precious');
        file_put_contents($doomed.'/nested/file.txt', 'goes away');
        symlink($outside, $doomed.'/nested/link-to-outside');

        $this->invokeRemoveDirectory($doomed);

        $this->assertFileExists($outside.'/media.txt', 'removeDirectory must not delete files through a symlink');
        $this->assertDirectoryDoesNotExist($doomed, 'the target tree itself must be fully removed');
    }

    private function invokeClearDirectory(string $dir): void
    {
        $method = new \ReflectionMethod(CoreRestoreService::class, 'clearDirectory');
        $method->invoke($this->makeService(), $dir);
    }

    private function invokeRemoveDirectory(string $dir): void
    {
        $method = new \ReflectionMethod(CoreRestoreService::class, 'removeDirectory');
        $method->invoke($this->makeService(), $dir);
    }

    private function makeService(): CoreRestoreService
    {
        return app(CoreRestoreService::class);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isLink() || $item->isFile()) {
                @unlink($item->getPathname());
            } else {
                $this->deleteTree($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
