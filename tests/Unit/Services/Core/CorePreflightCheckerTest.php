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

declare(strict_types=1);

namespace Tests\Unit\Services\Core;

use App\Services\Core\CorePreflightChecker;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\PreflightResult;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * CorePreflightChecker against a throwaway core tree under
 * storage/framework/testing. Every host probe (extensions, writability,
 * free space, PHP version, version drift) is injected, so the outcome does
 * not depend on the machine — the dev container runs as root, where
 * is_writable() is always true.
 */
class CorePreflightCheckerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/preflight-'.uniqid());
        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $dir) {
            File::ensureDirectoryExists($this->root.'/'.$dir);
        }
        File::put($this->root.'/app/Example.php', str_repeat('x', 1000));
        File::put($this->root.'/dixlase.json', json_encode(['requires' => ['php' => '>=8.2']]));
        File::put($this->root.'/VERSION', "1.0.0\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_a_healthy_environment_passes(): void
    {
        $result = $this->checker()->run();

        $this->assertFalse($result->failed(), implode("\n", $result->lines()));
        $this->assertSame(
            ['php_version', 'php_extensions', 'write_permissions', 'disk_space', 'previous_run'],
            array_column($result->checks(), 'name')
        );
    }

    public function test_php_older_than_the_installed_requirement_fails(): void
    {
        $result = $this->checker(phpVersion: '8.1.30')->run();

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('requires PHP >=8.2 but this server runs PHP 8.1.30', $result->failureSummary());
    }

    public function test_missing_extension_fails_and_names_it(): void
    {
        $result = $this->checker(missingExtensions: ['zip', 'intl'])->run();

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('missing PHP extensions: zip', $result->failureSummary());
        $this->assertStringNotContainsString('intl', $result->failureSummary(), 'only required extensions are checked');
    }

    public function test_unwritable_source_directory_fails_and_names_it(): void
    {
        $result = $this->checker(unwritable: ['resources'])->run();

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not writable by the PHP user: resources', $result->failureSummary());
    }

    public function test_not_enough_disk_space_fails(): void
    {
        $result = $this->checker(freeBytes: 1024.0)->run();

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('disk_space', $result->failureSummary());
    }

    public function test_unknown_free_space_only_warns(): void
    {
        $result = $this->checker(freeBytes: null)->run();

        $this->assertFalse($result->failed());
        $this->assertContains('disk_space', array_column($result->warnings(), 'name'));
    }

    public function test_leftovers_of_an_interrupted_run_warn_but_do_not_block(): void
    {
        File::ensureDirectoryExists($this->root.'/vendor.old');
        File::ensureDirectoryExists($this->root.'/app.new');
        File::ensureDirectoryExists($this->root.'/storage/framework');
        File::put($this->root.'/storage/framework/maintenance.php', '<?php');

        $result = $this->checker()->run();

        $this->assertFalse($result->failed());
        $warning = collect($result->warnings())->firstWhere('name', 'previous_run');
        $this->assertNotNull($warning);
        $this->assertStringContainsString('vendor.old', $warning['message']);
        $this->assertStringContainsString('app.new', $warning['message']);
        $this->assertStringContainsString('maintenance mode is already on', $warning['message']);
    }

    public function test_version_file_drift_warns(): void
    {
        $result = $this->checker(drift: ['manifest_drifted' => true, 'on_disk' => '1.0.0', 'manifest' => '1.0.1'])->run();

        $this->assertFalse($result->failed());
        $this->assertContains('version_files', array_column($result->warnings(), 'name'));
    }

    public function test_archive_that_does_not_fit_fails_before_extraction(): void
    {
        $zip = $this->makeZip(200_000);

        $result = $this->checker(freeBytes: 100_000.0)->checkDownloadedArchive($zip, $this->root.'/storage/app/private/core-update/staging/x');

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('archive_space', $result->failureSummary());
    }

    public function test_archive_that_fits_passes(): void
    {
        $zip = $this->makeZip(1000);

        $result = $this->checker()->checkDownloadedArchive($zip, $this->root.'/storage/app/private/core-update/staging/x');

        $this->assertFalse($result->failed());
    }

    public function test_new_release_requiring_a_newer_php_fails(): void
    {
        $payload = $this->root.'/payload';
        File::ensureDirectoryExists($payload);
        File::put($payload.'/dixlase.json', json_encode(['requires' => ['php' => '>=8.4']]));

        $result = $this->checker(phpVersion: '8.3.12')->checkReleaseRequirements($payload);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('the new release requires PHP >=8.4 but this server runs PHP 8.3.12', $result->failureSummary());
    }

    public function test_new_release_without_a_php_requirement_passes(): void
    {
        $payload = $this->root.'/payload';
        File::ensureDirectoryExists($payload);

        $result = $this->checker()->checkReleaseRequirements($payload);

        $this->assertFalse($result->failed());
    }

    public function test_failure_summary_and_lines_are_readable(): void
    {
        $result = (new PreflightResult())
            ->add('a', PreflightResult::OK, 'fine')
            ->add('b', PreflightResult::FAIL, 'broken')
            ->add('c', PreflightResult::WARN, 'odd');

        $this->assertSame('b: broken', $result->failureSummary());
        $this->assertSame('[preflight] FAIL b: broken', $result->lines()[1]);
        $this->assertCount(1, $result->warnings());
    }

    /**
     * @param  list<string>  $missingExtensions
     * @param  list<string>  $unwritable  paths relative to the fake root
     * @param  array<string, mixed>  $drift
     */
    private function checker(
        string $phpVersion = '8.3.12',
        array $missingExtensions = [],
        array $unwritable = [],
        ?float $freeBytes = 50.0 * 1024 * 1024 * 1024,
        array $drift = ['manifest_drifted' => false],
    ): CorePreflightChecker {
        $root = $this->root;

        return new CorePreflightChecker(
            basePath: $root,
            extensionLoaded: static fn (string $ext): bool => ! in_array($ext, $missingExtensions, true),
            isWritable: static function (string $path) use ($root, $unwritable): bool {
                foreach ($unwritable as $relative) {
                    if ($path === $root.'/'.$relative) {
                        return false;
                    }
                }

                return true;
            },
            freeSpace: static fn (string $path): ?float => $freeBytes,
            phpVersion: $phpVersion,
            driftDetector: static fn (): array => $drift,
        );
    }

    private function makeZip(int $bytes): string
    {
        $path = $this->root.'/release.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('dixlase-core/app/Big.php', str_repeat('a', $bytes));
        $zip->close();

        return $path;
    }
}
