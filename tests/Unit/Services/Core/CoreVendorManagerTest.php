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

use App\Services\Core\CoreVendorManager;
use Tests\TestCase;

/**
 * Pins the vendor/-swap behaviour shared by dependency-aware core updates
 * (CoreUpdater) and the rollback path (CoreRestoreService).
 *
 * Production installs are not guaranteed to have Composer/Node, so a core
 * operation that changes composer.lock swaps in a prebuilt vendor/
 * wholesale, retaining the previous one at vendor.old for a local rollback.
 * These tests exercise the detection gate (lockChanged / locksMatch) and
 * the swap / rollback / cleanup helpers against a throw-away directory
 * tree, never the real project root.
 */
class CoreVendorManagerTest extends TestCase
{
    private string $workDir;

    private CoreVendorManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-vendor-manager-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
        $this->manager = app(CoreVendorManager::class);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_lock_changed_is_false_when_locks_match(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($base.'/composer.lock', '{"content-hash":"abc"}');
        file_put_contents($staged.'/composer.lock', '{"content-hash":"abc"}');

        $this->assertFalse($this->manager->lockChanged($staged, $base));
    }

    public function test_lock_changed_is_true_when_package_set_differs(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        // Same shape, different package version — a real dependency
        // change that must trigger a vendor swap.
        file_put_contents($base.'/composer.lock', json_encode([
            'packages' => [['name' => 'vendor/pkg', 'version' => '1.0.0']],
            'packages-dev' => [],
            'platform' => ['php' => '^8.2'],
        ]));
        file_put_contents($staged.'/composer.lock', json_encode([
            'packages' => [['name' => 'vendor/pkg', 'version' => '2.0.0']],
            'packages-dev' => [],
            'platform' => ['php' => '^8.2'],
        ]));

        $this->assertTrue($this->manager->lockChanged($staged, $base));
    }

    public function test_lock_changed_is_false_when_release_ships_no_lock(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($base.'/composer.lock', '{"content-hash":"abc"}');

        $this->assertFalse($this->manager->lockChanged($staged, $base));
    }

    public function test_lock_changed_is_true_when_live_has_no_lock(): void
    {
        $base = $this->makeDir('base');
        $staged = $this->makeDir('staged');
        file_put_contents($staged.'/composer.lock', '{"content-hash":"xyz"}');

        $this->assertTrue($this->manager->lockChanged($staged, $base));
    }

    public function test_locks_match_compares_two_arbitrary_files(): void
    {
        $a = $this->workDir.'/a.lock';
        $b = $this->workDir.'/b.lock';
        $c = $this->workDir.'/c.lock';
        file_put_contents($a, 'same');
        file_put_contents($b, 'same');
        file_put_contents($c, 'different');

        $this->assertTrue($this->manager->locksMatch($a, $b));
        $this->assertFalse($this->manager->locksMatch($a, $c));
        $this->assertFalse($this->manager->locksMatch($a, $this->workDir.'/missing.lock'));
        $this->assertTrue($this->manager->locksMatch(null, $this->workDir.'/missing.lock'));
    }

    public function test_swap_swaps_in_staged_and_retains_old(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->manager->swap($payload, $base);

        $this->assertSame('NEW', file_get_contents($base.'/vendor/marker.txt'));
        $this->assertSame('OLD', file_get_contents($base.'/vendor.old/marker.txt'),
            'previous vendor/ must be retained at vendor.old for rollback');
    }

    public function test_restore_previous_rolls_back_a_swap(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->manager->swap($payload, $base);
        $this->manager->restorePrevious($base);

        $this->assertSame('OLD', file_get_contents($base.'/vendor/marker.txt'),
            'rollback must restore the previous vendor/');
        $this->assertDirectoryDoesNotExist($base.'/vendor.old',
            'vendor.old must be consumed by the rollback');
    }

    public function test_discard_previous_drops_retained_copy(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');
        $this->writeFile($payload.'/vendor/marker.txt', 'NEW');

        $this->manager->swap($payload, $base);
        $this->manager->discardPrevious($base);

        $this->assertSame('NEW', file_get_contents($base.'/vendor/marker.txt'));
        $this->assertDirectoryDoesNotExist($base.'/vendor.old',
            'a successful operation must discard vendor.old');
    }

    public function test_swap_throws_when_staged_vendor_missing(): void
    {
        $base = $this->makeDir('base');
        $payload = $this->makeDir('payload');
        $this->writeFile($base.'/vendor/marker.txt', 'OLD');

        $this->expectException(\RuntimeException::class);
        $this->manager->swap($payload, $base);
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
