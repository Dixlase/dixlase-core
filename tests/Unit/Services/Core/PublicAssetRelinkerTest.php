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

use App\Services\Core\PublicAssetRelinker;
use Tests\TestCase;

/**
 * Covers PublicAssetRelinker::ensureLink(), the repair step shared by the
 * core update, rollback, backup-restore and the symlink commands.
 *
 * The cases that matter are the ones the old `! File::exists($link)` guard
 * got wrong: a real directory sitting where the symlink belongs (what a
 * dereferenced release payload leaves behind) was treated as "already
 * linked", so the repair silently did nothing.
 *
 * Filesystem only — no database, no extension models.
 */
class PublicAssetRelinkerTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/public-asset-relinker-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir.'/themes/DixlaseOnePage/resources/assets', 0755, true);
        mkdir($this->workDir.'/public/assets/themes', 0755, true);
        file_put_contents($this->workDir.'/themes/DixlaseOnePage/resources/assets/style.css', 'theme css');
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_it_creates_a_relative_link_when_the_slot_is_empty(): void
    {
        $this->assertTrue(PublicAssetRelinker::ensureLink($this->target(), $this->link()));

        $this->assertTrue(is_link($this->link()));
        $this->assertSame('../../../themes/DixlaseOnePage/resources/assets', readlink($this->link()));
        $this->assertSame('theme css', (string) file_get_contents($this->link().'/style.css'));
    }

    public function test_it_replaces_a_real_directory_left_by_a_dereferenced_payload(): void
    {
        mkdir($this->link(), 0755, true);
        file_put_contents($this->link().'/style.css', 'stale copy from the zip');

        $this->assertTrue(PublicAssetRelinker::ensureLink($this->target(), $this->link()));

        $this->assertTrue(is_link($this->link()), 'A real directory must not keep the symlink out of its slot.');
        $this->assertSame('theme css', (string) file_get_contents($this->link().'/style.css'));
    }

    public function test_it_replaces_a_dangling_link(): void
    {
        symlink($this->workDir.'/themes/Gone/resources/assets', $this->link());
        $this->assertFalse(file_exists($this->link()));

        $this->assertTrue(PublicAssetRelinker::ensureLink($this->target(), $this->link()));

        $this->assertSame('theme css', (string) file_get_contents($this->link().'/style.css'));
    }

    public function test_it_leaves_a_working_link_untouched(): void
    {
        PublicAssetRelinker::ensureLink($this->target(), $this->link());
        $before = lstat($this->link());

        $this->assertTrue(PublicAssetRelinker::ensureLink($this->target(), $this->link()));

        $this->assertSame($before['ino'], lstat($this->link())['ino'], 'An already-correct link must not be recreated.');
    }

    public function test_it_repoints_a_link_aimed_somewhere_else(): void
    {
        mkdir($this->workDir.'/themes/Other/resources/assets', 0755, true);
        symlink($this->workDir.'/themes/Other/resources/assets', $this->link());

        $this->assertTrue(PublicAssetRelinker::ensureLink($this->target(), $this->link()));

        $this->assertSame('theme css', (string) file_get_contents($this->link().'/style.css'));
    }

    public function test_it_does_nothing_when_the_extension_has_no_assets(): void
    {
        $missing = $this->workDir.'/themes/NoAssets/resources/assets';

        $this->assertFalse(PublicAssetRelinker::ensureLink($missing, $this->link()));
        $this->assertFalse(is_link($this->link()));
        $this->assertFalse(file_exists($this->link()));
    }

    private function target(): string
    {
        return $this->workDir.'/themes/DixlaseOnePage/resources/assets';
    }

    private function link(): string
    {
        return $this->workDir.'/public/assets/themes/DixlaseOnePage';
    }

    private function deleteTree(string $path): void
    {
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }

        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->deleteTree($path.'/'.$entry);
        }

        @rmdir($path);
    }
}
