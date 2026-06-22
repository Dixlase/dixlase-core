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

namespace Tests\Unit\Services\Core;

use App\Services\Core\CoreUpdater;
use Tests\TestCase;

/**
 * Pins the vendor/-swap behaviour used by dependency-aware core updates.
 *
 * Production installs are not guaranteed to have Composer/Node, so a core
 * update that changes composer.lock swaps in the release's prebuilt
 * vendor/ wholesale, retaining the previous one at vendor.old so a failed
 * update can be rolled back locally. These tests exercise the decision gate
 * (dependencyLockChanged) and the swap / rollback / cleanup helpers against
 * a throw-away directory tree, never the real project root.
 */
class CoreUpdaterVendorTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-updater-vendor-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_dependency_lock_changed_is_false_when_locks_match(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($base.'/composer.lock', '{"content-hash":"abc"}');
        file_put_contents($staged.'/composer.lock', '{"content-hash":"abc"}');

        $this->assertFalse($this->invoke('dependencyLockChanged', [$staged, $base]));
    }

    public function test_dependency_lock_changed_is_true_when_locks_differ(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($base.'/composer.lock', '{"content-hash":"abc"}');
        file_put_contents($staged.'/composer.lock', '{"content-hash":"xyz"}');

        $this->assertTrue($this->invoke('dependencyLockChanged', [$staged, $base]));
    }

    public function test_dependency_lock_changed_is_false_when_release_ships_no_lock(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($base.'/composer.lock', '{"content-hash":"abc"}');

        $this->assertFalse($this->invoke('dependencyLockChanged', [$staged, $base]));
    }

    public function test_dependency_lock_changed_is_true_when_live_has_no_lock(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($staged.'/composer.lock', '{"content-hash":"xyz"}');

        $this->assertTrue($this->invoke('dependencyLockChanged', [$staged, $base]));
    }

    public function test_apply_vendor_swaps_in_staged_and_retains_old(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->invoke('applyVendor', [$payload, $base]);

        $this->assertSame('NEW', file_get_contents($base.'/vendor/marker.txt'));
        $this->assertSame('OLD', file_get_contents($base.'/vendor.old/marker.txt'),
            'previous vendor/ must be retained at vendor.old for rollback');
    }

    public function test_restore_old_vendor_rolls_back_a_swap(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->invoke('applyVendor', [$payload, $base]);
        $this->invoke('restoreOldVendor', [$base]);

        $this->assertSame('OLD', file_get_contents($base.'/vendor/marker.txt'),
            'rollback must restore the previous vendor/');
        $this->assertDirectoryDoesNotExist($base.'/vendor.old',
            'vendor.old must be consumed by the rollback');
    }

    public function test_cleanup_old_vendor_discards_retained_copy(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->invoke('applyVendor', [$payload, $base]);
        $this->invoke('cleanupOldVendor', [$base]);

        $this->assertSame('NEW', file_get_contents($base.'/vendor/marker.txt'));
        $this->assertDirectoryDoesNotExist($base.'/vendor.old',
            'a successful update must discard vendor.old');
    }

    public function test_apply_vendor_throws_when_staged_vendor_missing(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');

        $this->expectException(\RuntimeException::class);
        $this->invoke('applyVendor', [$payload, $base]);
    }

    private function invoke(string $method, array $args): mixed
    {
        $reflection = new \ReflectionMethod(CoreUpdater::class, $method);

        return $reflection->invokeArgs(app(CoreUpdater::class), $args);
    }

    private function makeDir(string $name): string
    {
        $dir = $this->workDir.'/'.$name;
        mkdir($dir, 0755, true);

        return $dir;
    }

    private function writeFile(string $path, string $contents): void
    {
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $contents);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
