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

use App\Support\Install\InstallRunLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `dls:install` exists so the one-liner installer can finish without a
 * browser: PHP's built-in server restarts on every .env write, which is
 * most of what an installation does.
 *
 * These tests cover the ways the command refuses to act. A run that
 * succeeds rewrites .env and drops every table, so it is never exercised
 * here — InstallRunner, which does that work, is shared with the wizard
 * and covered separately.
 */
class InstallCommandTest extends TestCase
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

    public function test_it_names_the_settings_it_still_needs(): void
    {
        $this->artisan('dls:install --no-interaction')
            ->assertExitCode(1);
    }

    public function test_it_refuses_to_write_without_confirmation(): void
    {
        $this->artisan('dls:install '.$this->commandOptions())
            ->expectsOutputToContain('Refusing to write without confirmation')
            ->assertExitCode(1);
    }

    public function test_it_rejects_settings_the_wizard_would_reject(): void
    {
        $this->artisan('dls:install '.$this->commandOptions([
            '--admin-email' => 'not-an-email',
            '--admin-password' => 'short',
        ]).' --force')
            ->expectsOutputToContain('These settings are not usable')
            ->assertExitCode(1);
    }

    public function test_it_rejects_an_admin_name_that_is_not_alphanumeric(): void
    {
        $this->artisan('dls:install '.$this->commandOptions(['--admin-name' => 'ad min!']).' --force')
            ->expectsOutputToContain('These settings are not usable')
            ->assertExitCode(1);
    }

    public function test_it_will_not_start_while_another_run_holds_the_lock(): void
    {
        InstallRunLock::acquire();

        $this->artisan('dls:install '.$this->commandOptions().' --force')
            ->expectsOutputToContain('already running')
            ->assertExitCode(1);
    }

    public function test_it_refuses_when_the_installation_is_already_complete(): void
    {
        $_SERVER['INSTALLED'] = 'true';

        try {
            $this->artisan('dls:install '.$this->commandOptions().' --force')
                ->expectsOutputToContain('already complete')
                ->assertExitCode(1);
        } finally {
            unset($_SERVER['INSTALLED']);
        }
    }

    /**
     * A full, valid set of options — every test above changes one thing.
     *
     * @param  array<string, string>  $overrides
     */
    private function commandOptions(array $overrides = []): string
    {
        $options = array_merge([
            '--site-name' => 'Test Site',
            '--admin-name' => 'admin',
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'Password123',
            '--url' => 'example.com',
            '--admin-url' => 'admin-abcd1234',
            '--db' => 'sqlite',
            '--db-database' => storage_path('framework/testing/never-created.sqlite'),
            '--no-interaction' => '',
        ], $overrides);

        return implode(' ', array_map(
            fn ($key, $value) => $value === '' ? $key : $key.'='.escapeshellarg((string) $value),
            array_keys($options),
            $options
        ));
    }
}
