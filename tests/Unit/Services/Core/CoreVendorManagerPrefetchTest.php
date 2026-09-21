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
use App\Services\Extension\ExtensionSourceManager;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * Pins the prebuilt-vendor check a dependency rollback runs before it
 * changes anything.
 *
 * On the production sandbox a rollback to v0.3.32 aborted half-way: that
 * release had no prebuilt-vendor asset, so the download fell back to
 * GitHub's source zipball, and "no vendor/ directory" surfaced only after
 * maintenance, the schema rollback and the source restore had already run.
 */
class CoreVendorManagerPrefetchTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/core-vendor-prefetch-'.getmypid().'-'.uniqid();
        mkdir($this->workDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->workDir);
        Mockery::close();
        parent::tearDown();
    }

    public function test_prefetch_returns_the_zip_when_the_release_ships_a_vendor_tree(): void
    {
        $zip = $this->buildZip([
            'dixlase-v0.3.35/composer.lock' => '{}',
            'dixlase-v0.3.35/vendor/autoload.php' => "<?php\n",
            'dixlase-v0.3.35/vendor/composer/ClassLoader.php' => "<?php\n",
        ]);

        $this->assertSame($zip, $this->managerDownloading($zip)->prefetchVerifiedRelease('0.3.35'));
    }

    public function test_prefetch_refuses_a_source_zipball_without_vendor(): void
    {
        // A GitHub source zipball: nested root, and the only "vendor"
        // directories are asset folders deep in resources/.
        $zip = $this->buildZip([
            'Dixlase-dixlase-core-abc1234/composer.lock' => '{}',
            'Dixlase-dixlase-core-abc1234/app/Models/Member.php' => "<?php\n",
            'Dixlase-dixlase-core-abc1234/resources/views/vendor/mail/html/layout.blade.php' => 'x',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no prebuilt vendor/');

        $this->managerDownloading($zip)->prefetchVerifiedRelease('0.3.32');
    }

    public function test_refetch_reuses_a_prefetched_zip_instead_of_downloading_again(): void
    {
        $zip = $this->buildZip([
            'dixlase-v0.3.35/vendor/autoload.php' => "<?php\n",
        ]);

        $sources = Mockery::mock(ExtensionSourceManager::class);
        $sources->shouldNotReceive('downloadCore');

        $base = $this->workDir.'/live';
        mkdir($base.'/vendor', 0755, true);
        file_put_contents($base.'/vendor/old.txt', 'old');

        (new CoreVendorManager($sources))->refetchAndSwap('0.3.35', null, $base, $zip);

        $this->assertFileExists($base.'/vendor/autoload.php');
        $this->assertFileExists($base.'/vendor.old/old.txt');

        // Clean the swapped trees; tearDown only removes plain files.
        foreach ([$base.'/vendor/autoload.php', $base.'/vendor.old/old.txt'] as $file) {
            @unlink($file);
        }
        @rmdir($base.'/vendor');
        @rmdir($base.'/vendor.old');
        @rmdir($base);
    }

    private function managerDownloading(string $zipPath): CoreVendorManager
    {
        $sources = Mockery::mock(ExtensionSourceManager::class);
        $sources->shouldReceive('downloadCore')->once()->andReturn($zipPath);

        return new CoreVendorManager($sources);
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function buildZip(array $entries): string
    {
        $path = $this->workDir.'/release-'.uniqid().'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $path;
    }
}
