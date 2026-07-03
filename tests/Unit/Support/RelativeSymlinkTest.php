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

namespace Tests\Unit\Support;

use App\Support\RelativeSymlink;
use Tests\TestCase;

/**
 * Pins the relative-path decomposition and the on-disk create()
 * behaviour of {@see \App\Support\RelativeSymlink}.
 *
 * The whole point of this helper is that the produced symlink target
 * resolves from any container that mounts the app at some path, so
 * the important assertions are (a) the recorded target string is
 * relative — no leading slash — and (b) it produces the shortest
 * hop-count between the two locations.
 */
class RelativeSymlinkTest extends TestCase
{
    /**
     * @dataProvider relativePathCases
     */
    public function test_relative_path_computation(string $to, string $from, string $expected): void
    {
        $this->assertSame($expected, RelativeSymlink::relativePath($to, $from));
    }

    public static function relativePathCases(): array
    {
        return [
            // The regression drivers: the two symlink layouts that
            // triggered this whole overhaul on Brand prod.
            'plugin assets link' => [
                '/var/www/html/plugins/DixlaseSEO/resources/assets',
                '/var/www/html/public/assets/plugins',
                '../../../plugins/DixlaseSEO/resources/assets',
            ],
            'storage public link' => [
                '/var/www/html/storage/app/public',
                '/var/www/html/public',
                '../storage/app/public',
            ],

            // Structural cases — decomposition rules the algorithm
            // must respect on every input.
            'child directory' => ['/a/b/c', '/a/b', 'c'],
            'parent directory' => ['/a/b', '/a/b/c', '..'],
            'siblings' => ['/a/b', '/a/c', '../b'],
            'identical' => ['/a/b', '/a/b', '.'],
            'no common prefix' => ['/x/y', '/a/b', '../../x/y'],
            'deep child' => ['/a/b/c/d/e', '/a/b', 'c/d/e'],

            // Trailing-slash normalisation. Neither input should
            // leak a trailing `/` into the recorded target because
            // symlink(2) would then reject or misinterpret it.
            'trailing slash on target' => ['/a/b/c/', '/a/b', 'c'],
            'trailing slash on base' => ['/a/b/c', '/a/b/', 'c'],

            // Redundant segments — the normaliser must collapse
            // `.` and `..` before computing the difference.
            'dot segments' => ['/a/./b/c', '/a/b', 'c'],
            'dotdot segments' => ['/a/b/x/../c', '/a/b', 'c'],
        ];
    }

    public function test_create_writes_a_relative_symlink_on_disk(): void
    {
        $tmpDir = sys_get_temp_dir().'/dls-relative-symlink-test-'.uniqid('', true);
        mkdir("{$tmpDir}/target-parent/target", 0777, true);
        mkdir("{$tmpDir}/link-parent", 0777, true);
        file_put_contents("{$tmpDir}/target-parent/target/marker.txt", 'ok');

        $target = "{$tmpDir}/target-parent/target";
        $link = "{$tmpDir}/link-parent/link";

        try {
            $this->assertTrue(RelativeSymlink::create($target, $link));
            $this->assertTrue(is_link($link), 'a symlink must be created at the requested path');

            $recordedTarget = readlink($link);
            $this->assertStringStartsNotWith(
                '/',
                $recordedTarget,
                'recorded target must be relative — an absolute target defeats the whole point of the helper',
            );
            $this->assertSame('../target-parent/target', $recordedTarget);

            // The symlink must actually be traversable from the link's
            // location — a well-formed relative target implies this.
            $this->assertSame('ok', file_get_contents("{$link}/marker.txt"));
        } finally {
            @unlink($link);
            @unlink("{$tmpDir}/target-parent/target/marker.txt");
            @rmdir("{$tmpDir}/target-parent/target");
            @rmdir("{$tmpDir}/target-parent");
            @rmdir("{$tmpDir}/link-parent");
            @rmdir($tmpDir);
        }
    }
}
