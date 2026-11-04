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

use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use Tests\TestCase;

/**
 * What the wizard's connection test accepts.
 *
 * The step answers while a site is not installed, so its input is not
 * operator input the way the CLI's arguments are: the driver must be one
 * whose PDO extension is loaded here, and a SQLite file stays inside the
 * application's own database directory.
 */
class InstallDatabaseTestConnectionInputTest extends TestCase
{
    private string $workDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        // The wizard answers while the site is not installed.
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
    }

    protected function tearDown(): void
    {
        if ($this->workDir !== '' && is_dir($this->workDir)) {
            @rmdir($this->workDir);
        }

        parent::tearDown();
    }

    public function test_a_sqlite_path_outside_the_database_directory_is_refused(): void
    {
        $this->workDir = storage_path('framework/testing/install-outside-'.uniqid());
        $target = $this->workDir.'/dixlase.sqlite';
        $this->assertDirectoryDoesNotExist($this->workDir);

        $response = $this->postJson(route('install.install.test-db'), [
            'db_connection' => 'sqlite',
            'db_database' => $target,
        ]);

        $response->assertStatus(422);
        $this->assertFileDoesNotExist($target);
        $this->assertDirectoryDoesNotExist($this->workDir, 'The refused path must not be created.');
    }

    public function test_a_sqlite_path_with_a_parent_segment_is_refused(): void
    {
        // A unique name, so a file an earlier run left behind cannot make
        // this pass or fail for the wrong reason.
        $name = 'install-parent-'.uniqid().'.sqlite';

        $response = $this->postJson(route('install.install.test-db'), [
            'db_connection' => 'sqlite',
            'db_database' => database_path('../storage/'.$name),
        ]);

        $response->assertStatus(422);
        $this->assertFileDoesNotExist(storage_path($name));
    }

    public function test_a_driver_that_is_not_available_is_refused(): void
    {
        $this->postJson(route('install.install.test-db'), [
            'db_connection' => 'pgsql',
            'db_database' => 'dixlase',
        ])->assertStatus(422);

        $this->postJson(route('install.install.test-db'), [
            'db_connection' => '../../etc/passwd',
            'db_database' => 'dixlase',
        ])->assertStatus(422);
    }

    public function test_the_default_sqlite_path_still_passes(): void
    {
        // An empty value means the bundled database/database.sqlite, which is
        // what a visitor who leaves the field alone sends.
        $response = $this->postJson(route('install.install.test-db'), [
            'db_connection' => 'sqlite',
            'db_database' => '',
        ]);

        $this->assertNotSame(
            422,
            $response->getStatusCode(),
            'The wizard must still be able to test the bundled SQLite file.'
        );
    }
}
