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

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallConfirmController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The wizard resolves and creates the SQLite file in three places (the
 * connection test, the mid-wizard .env write, and the final install), and
 * they must agree: Laravel's SQLite connector needs an absolute path to an
 * existing file and throws instead of creating one, so a disagreement shows
 * up as a failed migration half-way through the install.
 *
 * The helpers live on BaseInstallController; InstallConfirmController is
 * just a concrete subclass to reach them through.
 */
class InstallSqliteDatabasePathTest extends TestCase
{
    private string $workDir = '';

    protected function tearDown(): void
    {
        if ($this->workDir !== '' && is_dir($this->workDir)) {
            $this->deleteDirectory($this->workDir);
        }

        parent::tearDown();
    }

    public function test_an_empty_path_falls_back_to_the_bundled_database_file(): void
    {
        $this->assertSame(database_path('database.sqlite'), $this->resolvePath(''));
        $this->assertSame(database_path('database.sqlite'), $this->resolvePath(null));
        $this->assertSame(database_path('database.sqlite'), $this->resolvePath('   '));
    }

    public function test_a_relative_path_falls_back_to_the_bundled_database_file(): void
    {
        // The connector resolves a relative path against base_path(), which is
        // not where the wizard's own help text points, so the wizard never
        // passes one through.
        $this->assertSame(database_path('database.sqlite'), $this->resolvePath('database/database.sqlite'));
    }

    public function test_an_absolute_path_is_kept(): void
    {
        $this->assertSame('/var/data/dixlase.sqlite', $this->resolvePath('/var/data/dixlase.sqlite'));
        $this->assertSame('/var/data/dixlase.sqlite', $this->resolvePath('  /var/data/dixlase.sqlite  '));
    }

    public function test_the_database_file_and_its_directory_are_created(): void
    {
        $this->workDir = storage_path('framework/testing/sqlite-'.uniqid());
        $database = $this->workDir.'/nested/dixlase.sqlite';

        $this->assertTrue($this->ensureFile($database));
        $this->assertFileExists($database);
    }

    public function test_an_existing_file_is_left_alone(): void
    {
        $this->workDir = storage_path('framework/testing/sqlite-'.uniqid());
        mkdir($this->workDir, 0775, true);
        $database = $this->workDir.'/dixlase.sqlite';
        file_put_contents($database, 'existing');

        $this->assertTrue($this->ensureFile($database));
        $this->assertSame('existing', file_get_contents($database));
    }

    public function test_an_uncreatable_file_is_reported_instead_of_throwing(): void
    {
        $this->workDir = storage_path('framework/testing/sqlite-'.uniqid());
        mkdir($this->workDir, 0775, true);
        // A regular file where a directory would have to be.
        file_put_contents($this->workDir.'/blocker', '');

        $this->assertFalse($this->ensureFile($this->workDir.'/blocker/dixlase.sqlite'));
    }

    private function resolvePath(?string $database): string
    {
        $method = new ReflectionMethod(InstallConfirmController::class, 'resolveSqliteDatabasePath');
        $method->setAccessible(true);

        return $method->invoke(new InstallConfirmController(), $database);
    }

    private function ensureFile(string $database): bool
    {
        $method = new ReflectionMethod(InstallConfirmController::class, 'ensureSqliteDatabaseFile');
        $method->setAccessible(true);

        return $method->invoke(new InstallConfirmController(), $database);
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
