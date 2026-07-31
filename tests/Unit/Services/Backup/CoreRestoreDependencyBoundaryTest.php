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

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\Models\BackupRecord;
use App\Services\Backup\CoreRestoreService;
use Tests\TestCase;

/**
 * crossesDependencyBoundary() decides whether restoring a backup would wind
 * PHP dependencies back — the gate that routes a restore through the heavy,
 * maintenance-mode vendor re-fetch path instead of the plain local restore.
 *
 * The "live" side of the comparison is the installed composer.lock, so a
 * backup bundling an identical lock must read as "no boundary", and one
 * bundling a different lock as "boundary crossed". core_source must be a
 * target (only it carries composer.lock).
 */
class CoreRestoreDependencyBoundaryTest extends TestCase
{
    private string $workDir;

    private CoreRestoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/restore-depboundary-'.getmypid().'-'.uniqid();
        @mkdir($this->workDir, 0755, true);
        $this->service = app(CoreRestoreService::class);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->workDir)) {
            array_map('unlink', glob($this->workDir.'/*') ?: []);
            @rmdir($this->workDir);
        }
        parent::tearDown();
    }

    public function test_true_when_backup_lock_differs_from_installed(): void
    {
        $zip = $this->makeBackupZip(['composer.lock' => '{"packages":[{"name":"vendor/legacy-only","version":"1.0.0"}],"packages-dev":[],"platform":{"php":"^8.2"},"platform-dev":[]}']);
        $backup = $this->backupRecord([BackupServiceInterface::TARGET_CORE_SOURCE, BackupServiceInterface::TARGET_DATABASE], $zip);

        $this->assertTrue($this->service->crossesDependencyBoundary($backup));
    }

    public function test_false_when_backup_lock_matches_installed(): void
    {
        $live = base_path('composer.lock');
        $this->assertFileExists($live, 'this test assumes the project ships composer.lock');

        $zip = $this->makeBackupZip(['composer.lock' => (string) file_get_contents($live)]);
        $backup = $this->backupRecord([BackupServiceInterface::TARGET_CORE_SOURCE], $zip);

        $this->assertFalse($this->service->crossesDependencyBoundary($backup));
    }

    public function test_false_when_core_source_not_a_target(): void
    {
        $zip = $this->makeBackupZip(['composer.lock' => '{"packages":[{"name":"vendor/legacy-only","version":"1.0.0"}],"packages-dev":[],"platform":{"php":"^8.2"},"platform-dev":[]}']);
        $backup = $this->backupRecord([BackupServiceInterface::TARGET_DATABASE], $zip);

        $this->assertFalse($this->service->crossesDependencyBoundary($backup));
    }

    public function test_false_when_backup_has_no_lock(): void
    {
        $zip = $this->makeBackupZip(['app/Foo.php' => '<?php']);
        $backup = $this->backupRecord([BackupServiceInterface::TARGET_CORE_SOURCE], $zip);

        $this->assertFalse($this->service->crossesDependencyBoundary($backup));
    }

    public function test_reads_core_prefixed_lock_entry(): void
    {
        $zip = $this->makeBackupZip(['core/composer.lock' => '{"packages":[{"name":"vendor/legacy-only","version":"1.0.0"}],"packages-dev":[],"platform":{"php":"^8.2"},"platform-dev":[]}']);
        $backup = $this->backupRecord([BackupServiceInterface::TARGET_CORE_SOURCE], $zip);

        $this->assertTrue($this->service->crossesDependencyBoundary($backup));
    }

    /**
     * @param  array<string,string>  $entries
     */
    private function makeBackupZip(array $entries): string
    {
        $path = $this->workDir.'/backup-'.uniqid().'.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }

    /**
     * @param  string[]  $targets
     */
    private function backupRecord(array $targets, string $filePath): BackupRecord
    {
        $backup = new BackupRecord();
        $backup->targets = $targets;
        $backup->file_path = $filePath;

        return $backup;
    }
}
