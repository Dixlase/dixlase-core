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

namespace Tests\Feature\Install;

use App\Services\Core\CoreIntegrityVerifier;
use App\Services\Install\InstallRunner;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * The first thing an install does is the core integrity check, which
 * already queries the database. It has to reach the database the
 * operator chose (dls:install --db-host=…), not the one the template
 * .env names. The check is replaced here by a spy that records the
 * connection it would use and then stops the run, so nothing is written
 * to .env or to any database.
 */
class InstallRunnerDatabaseConfigTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $seen = null;

    protected function setUp(): void
    {
        parent::setUp();

        $verifier = Mockery::mock(CoreIntegrityVerifier::class);
        $verifier->shouldReceive('verify')->andReturnUsing(function () {
            $default = config('database.default');
            $this->seen = ['default' => $default] + (array) config("database.connections.{$default}");

            throw new RuntimeException('stop before .env is written');
        });
        $this->app->instance(CoreIntegrityVerifier::class, $verifier);
    }

    public function test_the_integrity_check_sees_the_chosen_database_server(): void
    {
        config(['database.connections.mysql.host' => '127.0.0.1']);

        $this->runUntilTheIntegrityCheck([
            'db_connection' => 'mysql',
            'db_host' => 'db.internal',
            'db_port' => '3307',
            'db_database' => 'dixlase',
            'db_username' => 'dixlase',
        ], 'secret');

        $this->assertSame('mysql', $this->seen['default']);
        $this->assertSame('db.internal', $this->seen['host']);
        $this->assertSame('3307', (string) $this->seen['port']);
        $this->assertSame('dixlase', $this->seen['database']);
        $this->assertSame('dixlase', $this->seen['username']);
        $this->assertSame('secret', $this->seen['password']);
    }

    public function test_sqlite_gets_its_file_and_no_credentials(): void
    {
        $path = storage_path('framework/testing/install-'.uniqid().'.sqlite');

        $this->runUntilTheIntegrityCheck([
            'db_connection' => 'sqlite',
            'db_host' => '',
            'db_port' => '',
            'db_database' => $path,
            'db_username' => '',
        ], 'ignored');

        $this->assertSame('sqlite', $this->seen['default']);
        $this->assertSame($path, $this->seen['database']);
        $this->assertFileDoesNotExist($path);
    }

    /**
     * @param  array<string, mixed>  $database
     */
    private function runUntilTheIntegrityCheck(array $database, string $dbPassword): void
    {
        try {
            app(InstallRunner::class)->run($database + [
                'site_name' => 'Test',
                'app_env' => 'local',
                'app_debug' => false,
                'app_url' => 'localhost',
                'force_ssl' => false,
                'admin_email' => 'admin@example.invalid',
            ], 'admin-password', $dbPassword);
            $this->fail('The run should have stopped at the integrity check.');
        } catch (RuntimeException $e) {
            $this->assertSame('stop before .env is written', $e->getMessage());
        }
    }
}
