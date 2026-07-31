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
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * Pins the post-extract integrity check that guards the rollback
 * vendor/ swap. Added in response to the dryrun-6 sandbox report
 * (Finding #3 in scratchpad/core-team-tasks.md), where a rollback
 * silently swapped in a truncated vendor/ (7694 / 7731 files) that
 * was missing the exception-page renderer so even error output was
 * broken.
 *
 * The verify helper compares the file-entry count recorded in the
 * ZIP against the actual on-disk count after extraction; a mismatch
 * throws before swap so the maintenance window stays open with the
 * old (working) vendor/ still live.
 */
class CoreVendorManagerVerifyTest extends TestCase
{
    private string $workDir;

    private CoreVendorManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-vendor-verify-'.getmypid().'-'.uniqid();
        $this->deleteTree($this->workDir);
        mkdir($this->workDir, 0755, true);
        $this->manager = app(CoreVendorManager::class);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->workDir);
        parent::tearDown();
    }

    public function test_verify_passes_when_extracted_count_matches_zip(): void
    {
        [$zipPath, $extractedRoot] = $this->buildZipAndExtract([
            'dixlase-v1.0.0/vendor/autoload.php' => "<?php\n",
            'dixlase-v1.0.0/vendor/composer/ClassLoader.php' => "<?php\n",
            'dixlase-v1.0.0/vendor/laravel/framework/README.md' => "framework\n",
        ]);

        // Should not throw.
        $this->invokeVerify($zipPath, $extractedRoot.'/vendor');
        $this->assertTrue(true);
    }

    public function test_verify_throws_when_extracted_tree_is_short(): void
    {
        [$zipPath, $extractedRoot] = $this->buildZipAndExtract([
            'dixlase-v1.0.0/vendor/a.php' => "a\n",
            'dixlase-v1.0.0/vendor/b.php' => "b\n",
            'dixlase-v1.0.0/vendor/c.php' => "c\n",
        ]);

        // Simulate a truncated extract: delete one file from disk.
        unlink($extractedRoot.'/vendor/b.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Extracted vendor/ is incomplete: expected 3 file entries from ZIP, found 2 on disk');

        $this->invokeVerify($zipPath, $extractedRoot.'/vendor');
    }

    public function test_zip_directory_entries_do_not_inflate_the_expected_count(): void
    {
        $zipPath = $this->workDir.'/with-dir-entries.zip';
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($zipPath, ZipArchive::CREATE));
        // Explicit dir entries (trailing slash) alongside actual files.
        $zip->addEmptyDir('dixlase-v1.0.0/vendor/composer');
        $zip->addEmptyDir('dixlase-v1.0.0/vendor/laravel');
        $zip->addFromString('dixlase-v1.0.0/vendor/autoload.php', "<?php\n");
        $zip->addFromString('dixlase-v1.0.0/vendor/composer/ClassLoader.php', "<?php\n");
        $zip->close();

        $count = $this->invokeCountZip($zipPath);

        $this->assertSame(
            2,
            $count,
            'Directory entries (trailing slash) inside the ZIP must not '
            .'be counted as file entries — otherwise the verify would '
            .'always report a phantom shortfall equal to the number of '
            .'empty-dir markers.'
        );
    }

    public function test_verify_handles_flat_zip_layout_without_top_level_dir(): void
    {
        // Some builds may drop the dixlase-vX.Y.Z/ wrapper — vendor/ sits
        // directly at the ZIP root. The regex must match both shapes.
        [$zipPath, $extractedRoot] = $this->buildZipAndExtract([
            'vendor/autoload.php' => "<?php\n",
            'vendor/composer/ClassLoader.php' => "<?php\n",
        ]);

        $this->invokeVerify($zipPath, $extractedRoot.'/vendor');
        $this->assertTrue(true);
    }

    /**
     * Build a ZIP with the given contents and extract it into a fresh
     * subdirectory of the workDir; return [$zipPath, $extractedRoot].
     *
     * $extractedRoot is the directory that would be returned by
     * CoreVendorManager::locateVendorPayload — i.e. the parent of vendor/.
     */
    private function buildZipAndExtract(array $files): array
    {
        $zipPath = $this->workDir.'/'.uniqid('release_', true).'.zip';
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($zipPath, ZipArchive::CREATE));
        foreach ($files as $entry => $body) {
            $zip->addFromString($entry, $body);
        }
        $zip->close();

        $stagingPath = $this->workDir.'/'.uniqid('stage_', true);
        mkdir($stagingPath, 0755, true);
        $extract = new ZipArchive();
        $this->assertSame(true, $extract->open($zipPath));
        $extract->extractTo($stagingPath);
        $extract->close();

        // Locate the payload root (mirrors CoreVendorManager::locateVendorPayload).
        if (is_dir($stagingPath.'/vendor')) {
            return [$zipPath, $stagingPath];
        }
        foreach (glob($stagingPath.'/*', GLOB_ONLYDIR) ?: [] as $candidate) {
            if (is_dir($candidate.'/vendor')) {
                return [$zipPath, $candidate];
            }
        }

        $this->fail('Test setup: extracted ZIP contains no vendor/ directory.');
    }

    private function invokeVerify(string $zipPath, string $extractedVendorDir): void
    {
        $method = new ReflectionMethod(CoreVendorManager::class, 'verifyExtractedVendorCount');
        $method->invoke($this->manager, $zipPath, $extractedVendorDir);
    }

    private function invokeCountZip(string $zipPath): int
    {
        $method = new ReflectionMethod(CoreVendorManager::class, 'countZipVendorFileEntries');

        return (int) $method->invoke($this->manager, $zipPath);
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
