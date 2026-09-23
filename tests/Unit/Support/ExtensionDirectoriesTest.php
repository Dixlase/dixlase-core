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

use App\Support\ExtensionDirectories;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An installed extension's directory name doubles as a PHP namespace
 * segment (`Plugins\{Name}\`), so it cannot contain a dot. Every name
 * that does is a copy left behind by an operator or by deploy tooling.
 *
 * Pure filesystem reads: no framework boot, no database.
 */
class ExtensionDirectoriesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/dls-extension-dirs-'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*') ?: [] as $dir) {
            @rmdir($dir);
        }
        @rmdir($this->root);

        parent::tearDown();
    }

    public function test_an_ordinary_extension_name_is_installed(): void
    {
        $this->assertTrue(ExtensionDirectories::isInstalledName('DixlaseOnePage'));
        $this->assertTrue(ExtensionDirectories::isInstalledName('DixlaseSEO'));
    }

    /**
     * @return list<array{0: string}>
     */
    public static function leftoverNames(): array
    {
        return [
            ['DixlaseOnePage.stale.20260705-033828'],
            ['DixlaseDeploy.bak'],
            ['DixlaseMenus.old'],
            ['.DixlaseHidden'],
            ['_DixlaseSetAside'],
            [''],
        ];
    }

    #[DataProvider('leftoverNames')]
    public function test_a_leftover_copy_is_not_installed(string $name): void
    {
        $this->assertFalse(ExtensionDirectories::isInstalledName($name));
    }

    public function test_list_returns_installed_directories_and_collects_the_rest(): void
    {
        mkdir($this->root.'/DixlaseOnePage');
        mkdir($this->root.'/DixlaseOnePage.stale.20260705-033828');
        mkdir($this->root.'/DixlaseSEO');
        mkdir($this->root.'/_scratch');

        $ignored = [];
        $directories = ExtensionDirectories::list($this->root, $ignored);

        $this->assertSame(
            [$this->root.'/DixlaseOnePage', $this->root.'/DixlaseSEO'],
            $directories,
        );
        $this->assertSame(['DixlaseOnePage.stale.20260705-033828', '_scratch'], $ignored);
    }

    public function test_list_on_a_missing_root_is_empty(): void
    {
        $ignored = [];

        $this->assertSame([], ExtensionDirectories::list($this->root.'/nope', $ignored));
        $this->assertSame([], $ignored);
    }
}
