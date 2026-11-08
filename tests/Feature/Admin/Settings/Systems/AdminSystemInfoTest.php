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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The page is hidden in simple mode, so these tests switch to advanced mode:
 * in simple mode the request is redirected before the controller runs.
 *
 * The suite runs on whichever database the environment gives it: SQLite
 * locally (phpunit.xml), MySQL in CI (test.yml). The first test holds on
 * both; the second checks the SQLite version itself and runs only there.
 */
class AdminSystemInfoTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    /**
     * What the three installed-flag sources held before this test.
     *
     * @var array<string, string|false|null>
     */
    private array $installedFlag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        // The flag is read as `$_SERVER ?? $_ENV ?? env() ?? config('app.installed')`,
        // so all three are set here and restored afterwards rather than reset,
        // the way #927 settled it: otherwise whatever an earlier test left in
        // $_SERVER decides this one, and this one decides the next.
        $this->installedFlag = [
            'getenv' => getenv('INSTALLED'),
            'env' => array_key_exists('INSTALLED', $_ENV) ? (string) $_ENV['INSTALLED'] : null,
            'server' => array_key_exists('INSTALLED', $_SERVER) ? (string) $_SERVER['INSTALLED'] : null,
        ];

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
        config()->set('app.installed', true);

        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);

        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->installedFlag['getenv'] === false) {
            putenv('INSTALLED');
        } else {
            putenv('INSTALLED='.$this->installedFlag['getenv']);
        }

        foreach ([['env', &$_ENV], ['server', &$_SERVER]] as [$key, &$bag]) {
            if ($this->installedFlag[$key] === null) {
                unset($bag['INSTALLED']);
            } else {
                $bag['INSTALLED'] = $this->installedFlag[$key];
            }
        }

        AdminModeHelper::clearCache();
        parent::tearDown();
    }

    public function test_the_page_opens_and_shows_the_database_version(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.info'))
            ->assertOk()
            ->assertViewHas('info', function (array $info): bool {
                $version = $info['Database']['version'] ?? null;

                return is_string($version) && $version !== '' && $version !== 'N/A';
            });
    }

    public function test_on_sqlite_the_page_shows_the_sqlite_version(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Checks the SQLite version, so it runs only on SQLite.');
        }

        $sqliteVersion = DB::selectOne('select sqlite_version() as version')->version;

        $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.info'))
            ->assertOk()
            ->assertViewHas('info', fn (array $info): bool => $info['Database']['version'] === $sqliteVersion)
            ->assertSee($sqliteVersion);
    }
}
