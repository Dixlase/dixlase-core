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
declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Services\PermissionRegistry;
use App\Support\ExtensionDirectories;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A directory segment appended to an extension root is one name.
 *
 * The result is read, and for a plugin's `config/admin/roles.php` it is
 * `require`d by PermissionRegistry::getPluginDefault(). The name reaches
 * that path from the role-permission form, whose `plugin_permissions`
 * keys are whatever the request carried, so a traversing segment would
 * resolve outside the plugins directory and include whatever it found.
 *
 * `isInstalledName()` was already the project's test for a usable
 * extension directory name -- eleven callers consult it, including a
 * validation rule -- and the path builder simply did not.
 */
class ExtensionDirectorySegmentTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function refusedSegments(): array
    {
        return [
            'parent' => ['..'],
            'current' => ['.'],
            'traversal' => ['../../etc'],
            'traversal in the middle' => ['Foo/../../etc'],
            'a separator at all' => ['Foo/Bar'],
            'a backslash' => ['Foo\\Bar'],
            'hidden' => ['.hidden'],
            'set aside' => ['_old'],
            'a move-aside copy' => ['DixlasePages.stale.20260101-000000'],
            'any dot' => ['Foo.Bar'],
        ];
    }

    #[DataProvider('refusedSegments')]
    public function test_the_plugins_root_refuses_a_segment_that_is_not_one_name(string $segment): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExtensionDirectories::pluginsRoot($segment);
    }

    #[DataProvider('refusedSegments')]
    public function test_the_themes_root_refuses_the_same(string $segment): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExtensionDirectories::themesRoot($segment);
    }

    public function test_an_ordinary_extension_name_still_resolves(): void
    {
        $this->assertSame(
            base_path('plugins').DIRECTORY_SEPARATOR.'DixlasePages',
            ExtensionDirectories::pluginsRoot('DixlasePages')
        );
        $this->assertSame(
            base_path('themes').DIRECTORY_SEPARATOR.'DixlaseOnePage',
            ExtensionDirectories::themesRoot('DixlaseOnePage')
        );
    }

    public function test_the_bare_root_is_unchanged(): void
    {
        $this->assertSame(base_path('plugins'), ExtensionDirectories::pluginsRoot());
        $this->assertSame(base_path('plugins'), ExtensionDirectories::pluginsRoot(''));
        $this->assertSame(base_path('themes'), ExtensionDirectories::themesRoot());
    }

    /**
     * The role-permission lookup answers "no definition" rather than
     * raising, because the name reaches it from a request. Its caller
     * already reads null as "this plugin declares nothing", which falls
     * back to ADMIN.
     */
    #[DataProvider('refusedSegments')]
    public function test_the_role_lookup_answers_nothing_for_such_a_segment(string $segment): void
    {
        $this->assertNull(PermissionRegistry::resolvePluginRolesPath($segment));
    }
}
