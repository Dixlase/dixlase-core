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

use App\Services\Install\InstallEnvironment;
use App\Support\Install\InstallRunLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The database and mail steps follow the install method (dixlase-core#883):
 * Docker starts with MySQL and Mailpit, every other method with SQLite and an
 * empty mail host, and each says so on the screen.
 */
class InstallMethodAwareStepsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        InstallRunLock::release();
        // Never let the test host's DNS decide; every case declares its method.
        $this->app->bind(InstallEnvironment::class, fn () => new class extends InstallEnvironment
        {
            protected function hostResolves(string $host): bool
            {
                return false;
            }
        });
    }

    protected function tearDown(): void
    {
        InstallRunLock::release();
        parent::tearDown();
    }

    public function test_docker_starts_the_database_step_with_mysql(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is not loaded, so MySQL cannot be offered.');
        }
        config(['dixlase.install_method' => 'docker']);

        $response = $this->withSession(['install_data' => $this->sessionBeforeDatabase()])
            ->get('/install/database');

        $response->assertOk();
        $response->assertViewHas('defaultDriver', 'mysql');
        $response->assertViewHas('isDocker', true);
        $response->assertSee(__('install/step3.detected_docker'));
        $response->assertSee(__('install/step3.mysql_docker'));
        $response->assertDontSee(__('install/step3.mysql_server_required'));
    }

    public function test_other_methods_start_the_database_step_with_sqlite(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded, so SQLite cannot be offered.');
        }

        foreach (['oneliner', 'composer', null] as $method) {
            config(['dixlase.install_method' => $method]);

            $response = $this->withSession(['install_data' => $this->sessionBeforeDatabase()])
                ->get('/install/database');

            $response->assertOk();
            $response->assertViewHas('defaultDriver', 'sqlite');
            $response->assertViewHas('isDocker', false);
            $response->assertDontSee(__('install/step3.detected_docker'));
            $response->assertSee(__('install/step3.mysql_server_required'));
            $response->assertSee(__('install/step3.sqlite_ready'));
        }
    }

    public function test_a_driver_already_chosen_is_kept(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is not loaded, so MySQL cannot be offered.');
        }
        config(['dixlase.install_method' => 'composer']);

        $session = array_merge($this->sessionBeforeDatabase(), ['db_connection' => 'mysql']);
        $response = $this->withSession(['install_data' => $session])->get('/install/database');

        $response->assertViewHas('defaultDriver', 'mysql');
    }

    public function test_docker_pre_fills_mailpit(): void
    {
        config(['dixlase.install_method' => 'docker']);

        $response = $this->withSession(['install_data' => $this->sessionBeforeMail()])
            ->get('/install/mail');

        $response->assertOk();
        $response->assertViewHas('installDefaults', ['mail_host' => 'mailpit', 'mail_port' => '1025']);
        $response->assertSee(__('install/step4.mailpit_ready'));
        $response->assertDontSee(__('install/step4.smtp_required'));
    }

    public function test_other_methods_leave_the_mail_host_empty(): void
    {
        config(['dixlase.install_method' => 'oneliner']);

        $response = $this->withSession(['install_data' => $this->sessionBeforeMail()])
            ->get('/install/mail');

        $response->assertOk();
        $response->assertViewHas('installDefaults', ['mail_host' => '', 'mail_port' => '587']);
        $response->assertDontSee('value="mailpit"', false);
        $response->assertSee(__('install/step4.smtp_required'));
    }

    /**
     * The session as it stands when the database step opens.
     *
     * @return array<string, mixed>
     */
    private function sessionBeforeDatabase(): array
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
        ];
    }

    /**
     * The session as it stands when the mail step opens (SQLite chosen).
     *
     * @return array<string, mixed>
     */
    private function sessionBeforeMail(): array
    {
        return array_merge($this->sessionBeforeDatabase(), [
            'db_connection' => 'sqlite',
            'db_host' => null,
            'db_port' => null,
            'db_database' => 'database/database.sqlite',
            'db_username' => null,
            'db_password' => null,
            'preserve_data' => false,
        ]);
    }
}
