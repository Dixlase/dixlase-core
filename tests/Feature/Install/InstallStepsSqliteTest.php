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

use App\Support\Install\InstallRunLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The step middleware lets a SQLite install past the database step.
 *
 * With SQLite the database form submits no host, port or user name, and
 * Laravel turns the empty inputs into null, so the session holds
 * `db_username => null`. The middleware checked every server field with
 * isset(), which is false for null, and sent each later step back to the
 * database screen with "Please complete the previous steps first"
 * (dixlase-core#866). The sessions below use null, the shape the wizard
 * really stores, not the empty strings a hand-written fixture tends to use.
 */
class InstallStepsSqliteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        InstallRunLock::release();
    }

    protected function tearDown(): void
    {
        InstallRunLock::release();
        parent::tearDown();
    }

    public function test_the_mail_step_opens_after_a_sqlite_database_step(): void
    {
        $response = $this->withSession(['install_data' => $this->sqliteSession()])
            ->get('/install/mail');

        $response->assertOk();
    }

    public function test_the_confirm_step_opens_after_a_sqlite_database_step(): void
    {
        $response = $this->withSession(['install_data' => $this->sqliteSession()])
            ->get('/install/confirm');

        $response->assertOk();
        $response->assertViewIs('install.confirm');
    }

    public function test_sqlite_still_needs_a_database_path(): void
    {
        $session = $this->sqliteSession();
        unset($session['db_database']);

        $response = $this->withSession(['install_data' => $session])->get('/install/mail');

        $response->assertRedirect(route('install.database'));
    }

    public function test_a_server_driver_still_needs_its_user_name(): void
    {
        $session = array_merge($this->sqliteSession(), [
            'db_connection' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'dixlase',
        ]);

        $response = $this->withSession(['install_data' => $session])->get('/install/mail');

        $response->assertRedirect(route('install.database'));
    }

    /**
     * A SQLite session as InstallDatabaseController stores it.
     *
     * @return array<string, mixed>
     */
    private function sqliteSession(): array
    {
        return [
            'install_mode' => 1,
            'site_name' => 'Test Site',
            'admin_account_name' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password',
            'app_env' => 'production',
            'app_url' => 'https://example.com',
            'admin_url' => 'admin-custom',
            'app_timezone' => 'UTC',
            'force_ssl' => true,
            'db_connection' => 'sqlite',
            'db_host' => null,
            'db_port' => null,
            'db_database' => 'database/database.sqlite',
            'db_username' => null,
            'db_password' => null,
            'preserve_data' => false,
        ];
    }
}
