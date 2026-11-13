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

namespace Tests\Feature\Admin;

use App\Enums\AdminMode;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\FrontPage;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The front page and update screens printed stored times as UTC, so a site
 * whose display timezone is not UTC read them nine hours out.
 */
class FrontAndUpdatesDatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

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

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->whereIn('name', ['display_timezone', 'admin_mode'])->delete();
        AdminModeHelper::clearCache();
        Carbon::setTestNow();
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

    public function test_the_front_page_screen_shows_the_last_update_in_the_display_timezone(): void
    {
        $page = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'content' => '<h1>Test</h1>',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
        DB::table('front_pages')->where('id', $page->id)->update(['updated_at' => '2026-03-01 15:30:45']);

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.front.index'))
            ->assertOk()
            ->assertSee('2026-03-02 00:30', false)
            ->assertSee('datetime="2026-03-01T15:30:45+00:00"', false);
    }

    public function test_the_updates_screen_shows_the_last_check_in_the_display_timezone(): void
    {
        // Opening the screen runs a version check, which stamps the check
        // time with now(); freezing the clock fixes that time.
        Carbon::setTestNow('2026-03-01 15:30:45');

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.updates.index'))
            ->assertOk()
            ->assertSee('2026/03/02 00:30', false)
            ->assertDontSee('2026/03/01 15:30', false);
    }
}
