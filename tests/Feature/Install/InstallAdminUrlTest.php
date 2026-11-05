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
 * The wizard never shows or saves an admin URL without its suffix.
 *
 * Only the mode step used to generate the admin URL, and the environment
 * step fell back to a bare `admin` when the session had none. The settings
 * step opens without the mode step, and a session can expire mid-wizard,
 * so the most guessable admin URL there is could be installed
 * (dixlase-core#884).
 */
class InstallAdminUrlTest extends TestCase
{
    use RefreshDatabase;

    private const COMPLETE = '/^(admin|manage|cp|panel|dash|ctrl)-[a-z0-9]{4,50}$/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        InstallRunLock::release();
    }

    protected function tearDown(): void
    {
        InstallRunLock::release();
        parent::tearDown();
    }

    public function test_the_environment_step_generates_a_missing_admin_url_in_simple_mode(): void
    {
        $response = $this->withSession(['install_data' => $this->settingsOnly(['install_mode' => 0])])
            ->get('/install/environment');

        $response->assertOk();
        $adminUrl = $response->viewData('adminUrl');
        $this->assertMatchesRegularExpression(self::COMPLETE, $adminUrl);
        $this->assertSame($adminUrl, session('install_data.admin_url'));
        $response->assertDontSee("/admin',", false);
    }

    public function test_the_environment_step_works_without_the_mode_step(): void
    {
        // No install_mode at all: the session the settings step leaves when it
        // is opened directly.
        $response = $this->withSession(['install_data' => $this->settingsOnly()])
            ->get('/install/environment');

        $response->assertOk();
        $this->assertMatchesRegularExpression(self::COMPLETE, $response->viewData('adminUrl'));
    }

    public function test_saving_the_environment_in_simple_mode_never_stores_a_bare_prefix(): void
    {
        $response = $this->withSession(['install_data' => $this->settingsOnly(['install_mode' => 0])])
            ->post('/install/environment', [
                'app_url' => 'example.com',
                'app_timezone' => 'UTC',
            ]);

        $response->assertRedirect(route('install.database'));
        $this->assertMatchesRegularExpression(self::COMPLETE, session('install_data.admin_url'));
    }

    public function test_a_bare_prefix_already_in_the_session_is_replaced(): void
    {
        $response = $this->withSession(['install_data' => $this->settingsOnly(['install_mode' => 0, 'admin_url' => 'admin'])])
            ->get('/install/environment');

        $this->assertMatchesRegularExpression(self::COMPLETE, $response->viewData('adminUrl'));
    }

    public function test_a_chosen_admin_url_is_kept(): void
    {
        $session = $this->settingsOnly(['install_mode' => 0, 'admin_url' => 'panel-custom']);

        $response = $this->withSession(['install_data' => $session])->get('/install/environment');
        $this->assertSame('panel-custom', $response->viewData('adminUrl'));

        $this->withSession(['install_data' => $session])->post('/install/mode', ['install_mode' => 1]);
        $this->assertSame('panel-custom', session('install_data.admin_url'));
    }

    /**
     * The session after the settings step, plus the given values.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function settingsOnly(array $extra = []): array
    {
        return array_merge([
            'site_name' => 'Test Site',
            'admin_account_name' => 'owner',
            'admin_email' => 'owner@example.com',
            'admin_password' => 'password',
        ], $extra);
    }
}
