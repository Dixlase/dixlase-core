<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace Tests\Unit\Services\Core;

use App\Services\Core\DependencyIntegrityService;
use PHPUnit\Framework\TestCase;

/**
 * The scenario these cover is the one that produced no other signal at all:
 * a core update interrupted between the source swap and the vendor swap. On
 * the sandbox 2026-09-24 the site answered 200, the log was clean and the
 * panel reported the new version, while `vendor/` was a release behind.
 *
 * Every case builds a throwaway tree under the system temp dir — never a real
 * checkout, which is why the service takes a base path.
 */
class DependencyIntegrityServiceTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir().'/dls-depint-'.uniqid();
        mkdir($this->base.'/vendor/composer', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->base);
        parent::tearDown();
    }

    public function test_a_matching_tree_is_ok(): void
    {
        $this->writeLock([
            ['name' => 'laravel/framework', 'version' => 'v13.33.0'],
            ['name' => 'livewire/livewire', 'version' => 'v4.4.6'],
        ]);
        $this->writeInstalled([
            'laravel/framework' => 'v13.33.0',
            'livewire/livewire' => 'v4.4.6',
        ]);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_OK, $result['state']);
        $this->assertSame(2, $result['checked']);
        $this->assertSame(0, $result['mismatched']);
    }

    public function test_an_interrupted_vendor_swap_is_detected(): void
    {
        // Source and lock moved to 0.3.39; vendor/ is still the 0.3.38 set.
        $this->writeLock([
            ['name' => 'laravel/framework', 'version' => 'v13.33.0'],
            ['name' => 'livewire/livewire', 'version' => 'v4.4.6'],
        ]);
        $this->writeInstalled([
            'laravel/framework' => 'v13.32.0',
            'livewire/livewire' => 'v4.4.6',
        ]);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_MISMATCHED, $result['state']);
        $this->assertSame(1, $result['mismatched']);
        $this->assertSame('laravel/framework', $result['samples'][0]['name']);
        $this->assertSame('v13.33.0', $result['samples'][0]['locked']);
        $this->assertSame('v13.32.0', $result['samples'][0]['installed']);
    }

    public function test_a_package_missing_from_vendor_is_a_mismatch(): void
    {
        // The shape left by a swap that died partway through extraction.
        $this->writeLock([
            ['name' => 'laravel/passkeys', 'version' => 'v1.0.0'],
        ]);
        $this->writeInstalled([]);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_MISMATCHED, $result['state']);
        $this->assertNull($result['samples'][0]['installed']);
    }

    public function test_dev_packages_are_not_compared(): void
    {
        // A production install runs `composer install --no-dev`, so the dev
        // set is legitimately absent. Comparing it would report a mismatch on
        // every correctly-installed production site.
        $this->writeLock(
            [['name' => 'laravel/framework', 'version' => 'v13.33.0']],
            [['name' => 'phpunit/phpunit', 'version' => '12.0.0']]
        );
        $this->writeInstalled(['laravel/framework' => 'v13.33.0']);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_OK, $result['state']);
        $this->assertSame(1, $result['checked'], 'only the production set is counted');
    }

    public function test_a_replaced_package_without_its_own_version_is_not_flagged(): void
    {
        // Composer records replaced/provided packages with no pretty_version.
        // Present-but-versionless is satisfied, not missing.
        $this->writeLock([['name' => 'psr/container', 'version' => '2.0.2']]);
        file_put_contents(
            $this->base.'/vendor/composer/installed.php',
            '<?php return '.var_export(['root' => [], 'versions' => ['psr/container' => ['replaced' => ['2.0.2']]]], true).';'
        );

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_OK, $result['state']);
    }

    public function test_a_missing_lockfile_is_unknown_not_a_mismatch(): void
    {
        $this->writeInstalled(['laravel/framework' => 'v13.33.0']);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_UNKNOWN, $result['state']);
        $this->assertStringContainsString('composer.lock', (string) $result['reason']);
    }

    public function test_a_missing_installed_file_is_unknown_not_a_mismatch(): void
    {
        $this->writeLock([['name' => 'laravel/framework', 'version' => 'v13.33.0']]);

        $result = (new DependencyIntegrityService())->check($this->base);

        $this->assertSame(DependencyIntegrityService::STATE_UNKNOWN, $result['state']);
        $this->assertStringContainsString('installed.php', (string) $result['reason']);
    }

    /**
     * @param  list<array{name: string, version: string}>  $packages
     * @param  list<array{name: string, version: string}>  $dev
     */
    private function writeLock(array $packages, array $dev = []): void
    {
        file_put_contents(
            $this->base.'/composer.lock',
            (string) json_encode(['packages' => $packages, 'packages-dev' => $dev])
        );
    }

    /**
     * @param  array<string, string>  $versions
     */
    private function writeInstalled(array $versions): void
    {
        $map = [];
        foreach ($versions as $name => $version) {
            $map[$name] = ['pretty_version' => $version, 'version' => $version];
        }
        file_put_contents(
            $this->base.'/vendor/composer/installed.php',
            '<?php return '.var_export(['root' => [], 'versions' => $map], true).';'
        );
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (array_diff((array) scandir($dir), ['.', '..']) as $entry) {
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
