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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The sidebar's icon rail is styled by `_sidebar-modes.scss`, which keys off
 * class and id markers in the admin layout and the sidebar partial, and the
 * head script that sets the rail class before the first paint. A Blade edit
 * that drops one of them breaks the rail without failing anything else, so
 * this test pins them on a rendered admin page.
 */
class AdminSidebarMarkersTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::create([
            'account_name' => 'sidebaradmin',
            'display_name' => 'Sidebar Admin',
            'email' => 'sidebar-admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';

        parent::tearDown();
    }

    private function dashboardHtml(): string
    {
        return $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();
    }

    public function test_the_head_script_sets_the_rail_class_before_the_first_paint(): void
    {
        $html = $this->dashboardHtml();

        $script = strpos($html, "classList.add('dls-sidebar-rail-active')");
        $this->assertNotFalse($script, 'The head script that adds dls-sidebar-rail-active is missing.');
        $this->assertLessThan(strpos($html, '</head>'), $script, 'The rail class must be set in <head>.');
    }

    public function test_the_sidebar_carries_the_markers_the_stylesheet_keys_off(): void
    {
        $html = $this->dashboardHtml();

        $this->assertMatchesRegularExpression('/<aside id="admin-sidebar"[^>]*class="[^"]*\bdls-admin-sidebar\b/', $html);
        $this->assertMatchesRegularExpression('/id="admin-sidebar-toggle"[^>]*aria-controls="admin-sidebar"/', $html);

        foreach ([
            'dls-sidebar-body',
            'dls-sidebar-nav',
            'dls-sidebar-top-link',
            'dls-sidebar-label',
            'dls-sidebar-panel',
            'dls-sidebar-child-link',
        ] as $marker) {
            $this->assertMatchesRegularExpression(
                '/class="[^"]*\b'.preg_quote($marker, '/').'(?![\w-])/',
                $html,
                "Marker class {$marker} is missing from the sidebar."
            );
        }
    }

    public function test_the_retired_collapse_state_is_only_read_for_migration(): void
    {
        $html = $this->dashboardHtml();

        $this->assertStringNotContainsString('id="fouc-sidebar"', $html);

        // The old key is still read once, so a stored preference carries
        // over to adminSidebarMode; nothing else may use it.
        $this->assertSame(
            substr_count($html, "localStorage.getItem('sidebarCollapsed')"),
            substr_count($html, 'sidebarCollapsed'),
            'sidebarCollapsed is used for something other than migrating the old preference.'
        );
    }
}
