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

use App\Services\Core\CoreVendorManager;
use Tests\TestCase;

/**
 * Pins the dependency-set fingerprint semantics that replaced the raw
 * byte hash in lockChanged / locksMatch. Added in response to the
 * dryrun-6 sandbox observation (Finding #4 in scratchpad/core-team-tasks.md)
 * that cosmetic lock churn — content-hash reshuffle, plugin-api-version
 * drift, key reordering — was tripping the update path into an
 * unnecessary vendor swap: a full maintenance window plus a fresh
 * download of an unchanged dependency set.
 *
 * The comparison now folds each lock to just packages+versions and
 * platform constraints, sorted, so noise-only edits do not misclassify
 * as a real dependency change. Corrupt / non-JSON files fall back to a
 * raw hash so the comparison still terminates for pathological input
 * (broken backup, truncated download, etc.).
 */
class CoreVendorManagerSmartLockTest extends TestCase
{
    private string $workDir;

    private CoreVendorManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-vendor-smart-lock-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
        $this->manager = app(CoreVendorManager::class);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_content_hash_only_diff_no_longer_counts_as_a_change(): void
    {
        // Regression pin for Finding #4: composer rewrites content-hash
        // even when the package set is unchanged (e.g. after a `composer
        // update` that resolved to the same versions). The pre-refactor
        // byte hash would classify this as "changed" and force a vendor
        // swap; the new fingerprint must not.
        $base = $this->makeStagedTree('base', [
            'content-hash' => 'OLD-HASH-BEFORE-COMPOSER-UPDATE',
            'plugin-api-version' => '2.6.0',
            'packages' => [
                ['name' => 'vendor/a', 'version' => '1.0.0'],
                ['name' => 'vendor/b', 'version' => '2.0.0'],
            ],
            'platform' => ['php' => '^8.2'],
        ]);
        $staged = $this->makeStagedTree('staged', [
            'content-hash' => 'NEW-HASH-AFTER-COMPOSER-UPDATE',
            'plugin-api-version' => '2.7.0',
            'packages' => [
                ['name' => 'vendor/a', 'version' => '1.0.0'],
                ['name' => 'vendor/b', 'version' => '2.0.0'],
            ],
            'platform' => ['php' => '^8.2'],
        ]);

        $this->assertFalse(
            $this->manager->lockChanged($staged, $base),
            'A lock diff that only touches content-hash / plugin-api-version '
            .'must not trigger a vendor swap — the dependency set is '
            .'identical (Finding #4 regression).'
        );
    }

    public function test_package_version_bump_is_still_detected(): void
    {
        $base = $this->makeStagedTree('base', [
            'packages' => [['name' => 'vendor/pkg', 'version' => '1.0.0']],
        ]);
        $staged = $this->makeStagedTree('staged', [
            'packages' => [['name' => 'vendor/pkg', 'version' => '1.1.0']],
        ]);

        $this->assertTrue($this->manager->lockChanged($staged, $base));
    }

    public function test_added_package_is_detected(): void
    {
        $base = $this->makeStagedTree('base', [
            'packages' => [['name' => 'vendor/a', 'version' => '1.0.0']],
        ]);
        $staged = $this->makeStagedTree('staged', [
            'packages' => [
                ['name' => 'vendor/a', 'version' => '1.0.0'],
                ['name' => 'vendor/b', 'version' => '1.0.0'],
            ],
        ]);

        $this->assertTrue($this->manager->lockChanged($staged, $base));
    }

    public function test_platform_change_is_detected(): void
    {
        $base = $this->makeStagedTree('base', [
            'packages' => [['name' => 'vendor/a', 'version' => '1.0.0']],
            'platform' => ['php' => '^8.2'],
        ]);
        $staged = $this->makeStagedTree('staged', [
            'packages' => [['name' => 'vendor/a', 'version' => '1.0.0']],
            'platform' => ['php' => '^8.3'],
        ]);

        $this->assertTrue(
            $this->manager->lockChanged($staged, $base),
            'A platform bump (e.g. PHP min version) must count as a '
            .'dependency change because the runtime contract shifted.'
        );
    }

    public function test_package_reordering_does_not_count_as_a_change(): void
    {
        // Fingerprint sorts packages by name so the source-lock key order
        // does not affect the result.
        $base = $this->makeStagedTree('base', [
            'packages' => [
                ['name' => 'vendor/a', 'version' => '1.0.0'],
                ['name' => 'vendor/b', 'version' => '2.0.0'],
            ],
        ]);
        $staged = $this->makeStagedTree('staged', [
            'packages' => [
                ['name' => 'vendor/b', 'version' => '2.0.0'],
                ['name' => 'vendor/a', 'version' => '1.0.0'],
            ],
        ]);

        $this->assertFalse($this->manager->lockChanged($staged, $base));
    }

    public function test_invalid_json_falls_back_to_raw_byte_comparison(): void
    {
        // Two invalid but byte-identical files stay equal; two invalid
        // and different files differ. This preserves the pre-refactor
        // semantics for pathological input (corrupt backup lock, etc.).
        $a = $this->workDir.'/a.lock';
        $b = $this->workDir.'/b.lock';
        $c = $this->workDir.'/c.lock';
        file_put_contents($a, 'not json at all');
        file_put_contents($b, 'not json at all');
        file_put_contents($c, 'a different broken lock');

        $this->assertTrue(
            $this->manager->locksMatch($a, $b),
            'Invalid-JSON files with identical bytes must still be reported '
            .'as matching to avoid a false-positive refetch on corrupt input.'
        );
        $this->assertFalse(
            $this->manager->locksMatch($a, $c),
            'Different invalid-JSON files must still be reported as '
            .'differing so the caller does not skip a needed reconciliation.'
        );
    }

    private function makeStagedTree(string $name, array $lockShape): string
    {
        $dir = $this->workDir.'/'.$name;
        mkdir($dir, 0755, true);
        // Fill in defaults so callers can pass just the interesting keys.
        $lockShape += [
            'packages' => [],
            'packages-dev' => [],
            'platform' => [],
            'platform-dev' => [],
        ];
        file_put_contents($dir.'/composer.lock', (string) json_encode($lockShape));

        return $dir;
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
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($path);
    }
}
