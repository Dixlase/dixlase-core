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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The confirm screen gates the install on a per-step required-field list.
 *
 * Two defects made that gate unusable: the database list assumed a server
 * driver (so every SQLite install was rejected), and the recovery redirect
 * built route names that do not exist (`install.database.create`), turning
 * "send the operator back to fix it" into a RouteNotFoundException.
 *
 * The step middleware checks the same fields with isset(), while the
 * controller checks with empty(), so a field that is present but blank —
 * exactly what the SQLite branch writes for host / port / username — reaches
 * the controller. The redirect cases below reproduce that shape.
 */
class InstallConfirmRequiredFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_screen_renders_for_a_sqlite_installation(): void
    {
        $response = $this->withSession(['install_data' => $this->sqliteSession()])
            ->get('/install/confirm');

        $response->assertStatus(200);
        $response->assertViewIs('install.confirm');
    }

    public function test_confirm_screen_renders_for_a_mysql_installation(): void
    {
        $response = $this->withSession(['install_data' => $this->mysqlSession()])
            ->get('/install/confirm');

        $response->assertStatus(200);
        $response->assertViewIs('install.confirm');
    }

    public function test_confirm_screen_renders_for_sqlite_with_leftover_server_values(): void
    {
        // The database step hides host / port / user with `x-show`, which does
        // not stop the browser submitting them, so a docker-preset session can
        // reach the confirm screen carrying MySQL values next to sqlite.
        $session = array_merge($this->sqliteSession(), [
            'db_host' => 'mysql',
            'db_port' => '3306',
            'db_username' => 'dixlase',
        ]);

        $response = $this->withSession(['install_data' => $session])->get('/install/confirm');

        $response->assertStatus(200);
        $response->assertViewIs('install.confirm');
    }

    public function test_sqlite_installation_still_needs_a_database_path(): void
    {
        $session = $this->sqliteSession();
        $session['db_database'] = '';

        $response = $this->withSession(['install_data' => $session])->get('/install/confirm');

        $response->assertRedirect(route('install.database'));
        $response->assertSessionHas('error');
    }

    public function test_a_blank_settings_field_redirects_to_the_settings_step(): void
    {
        $this->assertBlankFieldRedirects('site_name', 'install.settings');
    }

    public function test_a_blank_environment_field_redirects_to_the_environment_step(): void
    {
        $this->assertBlankFieldRedirects('app_url', 'install.environment');
    }

    public function test_a_blank_database_field_redirects_to_the_database_step(): void
    {
        $this->assertBlankFieldRedirects('db_username', 'install.database');
    }

    private function assertBlankFieldRedirects(string $field, string $route): void
    {
        $session = $this->mysqlSession();
        $session[$field] = '';

        $response = $this->withSession(['install_data' => $session])->get('/install/confirm');

        $response->assertRedirect(route($route));
        $response->assertSessionHas('error');
    }

    /**
     * A SQLite session as the database step actually leaves it: the server
     * fields are present but blank on purpose.
     *
     * @return array<string, mixed>
     */
    private function sqliteSession(): array
    {
        return array_merge($this->baseSession(), [
            'db_connection' => 'sqlite',
            'db_host' => '',
            'db_port' => '',
            'db_database' => 'database/database.sqlite',
            'db_username' => '',
            'db_password' => '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mysqlSession(): array
    {
        return array_merge($this->baseSession(), [
            'db_connection' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'dixlase',
            'db_username' => 'dixlase',
            'db_password' => 'secret',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseSession(): array
    {
        return [
            'install_mode' => 1,
            'site_name' => 'Test Site',
            'admin_account_name' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password',
            'app_env' => 'production',
            'app_url' => 'https://example.com',
            'admin_url' => 'admin',
            'app_timezone' => 'UTC',
            'force_ssl' => true,
        ];
    }
}
