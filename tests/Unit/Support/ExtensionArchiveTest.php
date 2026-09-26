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

use App\Support\ExtensionArchive;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * An uploaded extension ZIP must contain exactly one top-level directory, and
 * only that directory may reach the extension parent directory. The upload
 * path used to check the first directory and extract the whole archive, so a
 * second directory landed without the pending-install marker and an entry
 * could overwrite a file of an installed plugin.
 */
class ExtensionArchiveTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/extension-archive-'.uniqid());
        File::ensureDirectoryExists($this->root.'/plugins');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function zip(array $entries): ZipArchive
    {
        $path = $this->root.'/'.uniqid('upload-', true).'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $zip->open($path);

        return $zip;
    }

    public function test_a_single_root_archive_is_placed(): void
    {
        $zip = $this->zip(['Acme/plugin.json' => '{}', 'Acme/app/Foo.php' => '<?php']);

        $this->assertSame('Acme', ExtensionArchive::singleRootDirectory($zip));
        $this->assertTrue(ExtensionArchive::extractSingleRoot($zip, 'Acme', $this->root.'/plugins'));
        $this->assertFileExists($this->root.'/plugins/Acme/app/Foo.php');
    }

    public function test_a_second_top_level_directory_is_refused(): void
    {
        $zip = $this->zip([
            'Acme/plugin.json' => '{}',
            'Sneaky/composer.json' => '{"autoload":{"files":["boot.php"]}}',
            'Sneaky/boot.php' => '<?php',
        ]);

        $this->assertNull(ExtensionArchive::singleRootDirectory($zip));
    }

    public function test_an_entry_targeting_an_installed_plugin_is_refused(): void
    {
        $zip = $this->zip([
            'Acme/plugin.json' => '{}',
            'DixlaseSEO/config/zz.php' => '<?php',
        ]);

        $this->assertNull(ExtensionArchive::singleRootDirectory($zip));
    }

    public function test_files_at_the_archive_root_or_dot_segments_are_refused(): void
    {
        $this->assertNull(ExtensionArchive::singleRootDirectory($this->zip(['plugin.json' => '{}'])));
        $this->assertNull(ExtensionArchive::singleRootDirectory($this->zip(['Acme/../x.php' => '<?php'])));
    }

    public function test_an_existing_directory_is_never_overwritten(): void
    {
        File::ensureDirectoryExists($this->root.'/plugins/Acme');
        File::put($this->root.'/plugins/Acme/keep.txt', 'original');

        $zip = $this->zip(['Acme/keep.txt' => 'replaced']);

        $this->assertFalse(ExtensionArchive::extractSingleRoot($zip, 'Acme', $this->root.'/plugins'));
        $this->assertSame('original', File::get($this->root.'/plugins/Acme/keep.txt'));
    }
}
