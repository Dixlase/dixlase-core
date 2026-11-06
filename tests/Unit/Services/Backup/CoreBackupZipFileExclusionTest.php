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

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\CoreBackupService;
use App\Services\Verification\CoreFileVerificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `addDirectoryToZip()` can leave a single file out of a directory the
 * operator did ask for.
 *
 * The `logs` target uses it for `install.log` (#889). The projection is
 * exercised here against a throwaway tree rather than `storage/logs`,
 * so the test can assert what is *absent* without reading — or
 * removing — the logs of the checkout it runs in.
 *
 * @see \App\Services\Backup\CoreBackupService
 */
class CoreBackupZipFileExclusionTest extends TestCase
{
    private string $sourceDir;

    private string $zipPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = storage_path('framework/testing/zip-exclude-'.uniqid());
        @mkdir($this->sourceDir.'/nested', 0775, true);

        file_put_contents($this->sourceDir.'/install.log', "install chatter\n");
        file_put_contents($this->sourceDir.'/laravel.log', "application log\n");
        file_put_contents($this->sourceDir.'/nested/install.log', "a different file\n");

        $this->zipPath = storage_path('framework/testing/zip-exclude-'.uniqid().'.zip');
    }

    protected function tearDown(): void
    {
        foreach (['/install.log', '/laravel.log', '/nested/install.log'] as $f) {
            @unlink($this->sourceDir.$f);
        }
        @rmdir($this->sourceDir.'/nested');
        @rmdir($this->sourceDir);
        @unlink($this->zipPath);

        parent::tearDown();
    }

    /**
     * @param  string[]  $excludeFiles
     * @return string[]
     */
    private function entriesFor(array $excludeFiles): array
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($this->zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);

        $method = new \ReflectionMethod(CoreBackupService::class, 'addDirectoryToZip');
        $method->invoke(
            new CoreBackupService(new CoreFileVerificationService()),
            $zip,
            $this->sourceDir,
            'storage/logs',
            [],
            [],
            $excludeFiles,
        );

        $zip->close();

        $read = new \ZipArchive();
        $this->assertTrue($read->open($this->zipPath) === true);

        $entries = [];
        for ($i = 0; $i < $read->numFiles; $i++) {
            $entries[] = $read->getNameIndex($i);
        }
        $read->close();

        return $entries;
    }

    #[Test]
    public function an_excluded_file_is_left_out_and_its_neighbours_are_not(): void
    {
        $entries = $this->entriesFor(['install.log']);

        $this->assertNotContains('storage/logs/install.log', $entries);
        $this->assertContains('storage/logs/laravel.log', $entries);
    }

    #[Test]
    public function the_exclusion_is_a_relative_path_not_a_basename(): void
    {
        // A same-named file deeper in the tree is a different file. The
        // logs target means the one install.log directly under
        // storage/logs, so matching on the basename would overreach.
        $entries = $this->entriesFor(['install.log']);

        $this->assertContains('storage/logs/nested/install.log', $entries);
    }

    #[Test]
    public function nothing_is_left_out_when_no_exclusion_is_given(): void
    {
        $entries = $this->entriesFor([]);

        $this->assertContains('storage/logs/install.log', $entries);
        $this->assertContains('storage/logs/laravel.log', $entries);
        $this->assertContains('storage/logs/nested/install.log', $entries);
    }
}
