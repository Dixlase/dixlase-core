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

namespace Tests\Feature\Admin\Settings\Security;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetMemberLocale;
use App\Models\FileIntegrityAudit;
use App\Models\Member;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\FileIntegrityService;
use App\Services\Security\ScheduledSecurityCheckMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * The security screens printed stored times as they came off the model, so a
 * site whose display timezone is not UTC read every scan nine hours out.
 */
class SecurityDatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    private int $auditId;

    private mixed $serverInstalled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
            // The integrity screen picks its date format by locale, so the
            // locale stays the one set below on a site whose default is not English.
            SetAdminLocale::class,
            SetMemberLocale::class,
        ]);

        // ConfigHelper reads display_timezone from the database only when
        // INSTALLED is true, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $this->serverInstalled = $_SERVER['INSTALLED'] ?? null;
        $_SERVER['INSTALLED'] = 'true';

        // The service provider registers the admin view namespace only on an
        // installed site at boot, which a test is not.
        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        App::setLocale('en');

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');
        // Simple mode hides the integrity card on the overview.
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $audit = FileIntegrityAudit::create([
            'scan_uuid' => (string) Str::uuid(),
            'scope' => FileIntegrityAudit::SCOPE_CORE,
            'trigger' => FileIntegrityAudit::TRIGGER_MANUAL,
            'initiated_by_type' => FileIntegrityAudit::INITIATED_BY_USER,
            'status' => FileIntegrityAudit::STATUS_OK,
            'hash_algo' => 'sha256',
            'started_at' => '2026-03-01 15:30:40',
        ]);
        $this->auditId = $audit->id;

        DB::table('file_integrity_audits')->where('id', $this->auditId)->update([
            'created_at' => '2026-03-01 15:30:45',
        ]);
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->whereIn('name', ['display_timezone', 'admin_mode'])->delete();
        AdminModeHelper::clearCache();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        // Laravel reads $_SERVER before $_ENV, and phpunit.xml leaves it
        // unset; leaving 'false' behind would override later tests that
        // set only $_ENV.
        if ($this->serverInstalled === null) {
            unset($_SERVER['INSTALLED']);
        } else {
            $_SERVER['INSTALLED'] = $this->serverInstalled;
        }

        parent::tearDown();
    }

    private function superAdmin(): Member
    {
        return Member::factory()->create([
            'account_name' => 'superadminx',
            'email' => 'superadmin@example.com',
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);
    }

    public function test_the_security_overview_shows_the_scan_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.security.index'))
            ->assertOk()
            ->assertSee('2026-03-02 00:30', false)
            ->assertSee('datetime="2026-03-01T15:30:45+00:00"', false);
    }

    public function test_the_integrity_screen_shows_every_time_in_the_display_timezone(): void
    {
        $this->instance(FileIntegrityService::class, Mockery::mock(app(FileIntegrityService::class), function ($mock): void {
            $mock->shouldReceive('hasBaseline')->andReturn(true);
            $mock->shouldReceive('getBaselineMeta')->andReturn(['generated_at' => '2026-04-10T16:10:00+00:00']);
        }));
        $this->instance(ScheduledSecurityCheckMonitor::class, Mockery::mock(app(ScheduledSecurityCheckMonitor::class), function ($mock): void {
            $mock->shouldReceive('latestCoreManifestCheck')->andReturn([
                'status' => 'ok',
                'checked_at' => '2026-05-20T17:20:00+00:00',
            ]);
        }));

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.security.integrity'))
            ->assertOk()
            ->assertSee('datetime="2026-03-01T15:30:45+00:00"', false)
            ->assertSee('2026-03-02 00:30', false)
            ->assertSee('2026-04-11 01:10', false)
            ->assertDontSee('2026-04-10 16:10', false)
            ->assertSee('2026-05-21 02:20', false)
            ->assertDontSee('2026-05-20 17:20', false);
    }

    public function test_the_scan_detail_shows_the_time_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.security.integrity.show', $this->auditId))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45', false)
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }

    public function test_the_captcha_screen_shows_the_test_time_in_the_display_timezone(): void
    {
        SecuritySetting::updateOrCreate(['name' => 'captcha_authentication_result'], ['value' => '1']);
        SecuritySetting::query()->where('name', 'captcha_authentication_result')->update([
            'updated_at' => '2026-03-01 15:30:45',
        ]);

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.security.captcha'))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45', false)
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }
}
