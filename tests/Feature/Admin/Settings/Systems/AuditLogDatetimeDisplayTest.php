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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The audit log screens printed occurred_at straight off the model, so a site
 * whose display timezone is not UTC read every entry nine hours out. They now
 * go through <x-ui-datetime>, which converts to the display timezone.
 */
class AuditLogDatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    private int $logId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        // ConfigHelper reads display_timezone from the database only when
        // INSTALLED is true, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // The service provider registers the admin view namespace only on an
        // installed site at boot, which a test is not.
        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');

        $this->logId = DB::table('audit_logs')->insertGetId([
            'occurred_at' => '2026-03-01 15:30:45',
            'category' => 'test',
            'action' => 'fixture.datetime',
            'created_at' => '2026-03-01 15:30:46',
            'updated_at' => '2026-03-01 15:30:46',
        ]);
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->where('name', 'display_timezone')->delete();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

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

    public function test_the_list_shows_the_time_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.logs.index'))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45')
            ->assertDontSee('2026-03-01 15:30:45</span>', false)
            ->assertSee('datetime="2026-03-01T15:30:45+00:00"', false)
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }

    public function test_the_detail_shows_the_times_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.logs.audit.show', $this->logId))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45')
            ->assertSee('2026-03-02 00:30:46')
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }
}
