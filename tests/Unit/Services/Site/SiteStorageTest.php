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

declare(strict_types=1);

namespace Tests\Unit\Services\Site;

use App\Contracts\Site\SiteContextInterface;
use App\Services\Site\SiteStorage;
use Tests\TestCase;

/**
 * Verifies that SiteStorage roots disks at the multisite-aware storage tree:
 *
 *   storage/app/private/sites/{site_id}/  ← private()
 *   storage/app/public/sites/{site_id}/   ← public()
 *   storage/app/private/global/           ← global()
 *
 * The helper is the forward-looking API for plugins and themes; the path
 * shape is part of the Plugin API contract within ^0.1.
 */
class SiteStorageTest extends TestCase
{
    public function test_private_path_uses_current_site_id_when_omitted(): void
    {
        $this->bindSiteContextTo(42);

        $this->assertSame(
            storage_path('app/private/sites/42'),
            SiteStorage::privatePath()
        );
    }

    public function test_private_path_honors_explicit_site_id(): void
    {
        $this->bindSiteContextTo(1);

        $this->assertSame(
            storage_path('app/private/sites/7'),
            SiteStorage::privatePath(7)
        );
    }

    public function test_public_path_uses_current_site_id_when_omitted(): void
    {
        $this->bindSiteContextTo(3);

        $this->assertSame(
            storage_path('app/public/sites/3'),
            SiteStorage::publicPath()
        );
    }

    public function test_public_path_honors_explicit_site_id(): void
    {
        $this->bindSiteContextTo(1);

        $this->assertSame(
            storage_path('app/public/sites/9'),
            SiteStorage::publicPath(9)
        );
    }

    public function test_global_path_is_independent_of_site_context(): void
    {
        $this->bindSiteContextTo(1);

        $this->assertSame(
            storage_path('app/private/global'),
            SiteStorage::globalPath()
        );

        $this->bindSiteContextTo(99);

        $this->assertSame(
            storage_path('app/private/global'),
            SiteStorage::globalPath()
        );
    }

    public function test_private_disk_writes_under_site_specific_root(): void
    {
        $this->bindSiteContextTo(5);

        $disk = SiteStorage::private();
        $disk->put('verify.txt', 'hello');

        $this->assertFileExists(storage_path('app/private/sites/5/verify.txt'));
        $this->assertSame('hello', file_get_contents(storage_path('app/private/sites/5/verify.txt')));

        @unlink(storage_path('app/private/sites/5/verify.txt'));
        @rmdir(storage_path('app/private/sites/5'));
    }

    public function test_global_disk_writes_under_global_root(): void
    {
        $this->bindSiteContextTo(1);

        $disk = SiteStorage::global();
        $disk->put('network-wide.txt', 'shared');

        $this->assertFileExists(storage_path('app/private/global/network-wide.txt'));

        @unlink(storage_path('app/private/global/network-wide.txt'));
    }

    public function test_private_disk_is_isolated_per_site(): void
    {
        SiteStorage::private(10)->put('isolated.txt', 'site-10');
        SiteStorage::private(20)->put('isolated.txt', 'site-20');

        $this->assertSame('site-10', SiteStorage::private(10)->get('isolated.txt'));
        $this->assertSame('site-20', SiteStorage::private(20)->get('isolated.txt'));

        @unlink(storage_path('app/private/sites/10/isolated.txt'));
        @rmdir(storage_path('app/private/sites/10'));
        @unlink(storage_path('app/private/sites/20/isolated.txt'));
        @rmdir(storage_path('app/private/sites/20'));
    }

    private function bindSiteContextTo(int $siteId): void
    {
        $context = $this->createMock(SiteContextInterface::class);
        $context->method('currentSiteId')->willReturn($siteId);

        $this->app->instance(SiteContextInterface::class, $context);
    }
}
