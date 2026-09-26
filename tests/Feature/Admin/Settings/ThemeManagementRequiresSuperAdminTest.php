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

namespace Tests\Feature\Admin\Settings;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Theme management (upload, install, switch, update, delete) is SUPER_ADMIN.
 *
 * The routes were gated on the bare `settings.themes` key. That node has no
 * roles of its own, so PermissionRegistry answered from its children -- and
 * the ADMIN-level `settings.themes.settings` (the active theme's settings
 * page) opened every theme-management endpoint to ADMIN. A theme ships PHP,
 * so that was code execution for ADMIN.
 */
class ThemeManagementRequiresSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        Member::factory()->create([
            'id' => 1,
            'account_name' => 'owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    public function test_every_theme_management_route_is_gated_on_a_defined_super_admin_key(): void
    {
        $checked = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'admin.settings.themes.')) {
                continue;
            }
            $checked++;

            $middleware = $route->gatherMiddleware();
            $this->assertContains('check.menu.access:settings.themes.index', $middleware, $name);
            $this->assertNotContains('check.menu.access:settings.themes', $middleware, $name);
            $this->assertNotContains('check.menu.edit:settings.themes', $middleware, $name);

            if (! in_array('GET', $route->methods(), true)) {
                $this->assertContains('check.menu.edit:settings.themes.add', $middleware, $name);
            }
        }

        $this->assertGreaterThan(10, $checked);
        $this->assertFalse(PermissionRegistry::canAccess('settings.themes.index', MemberRole::ADMIN));
        $this->assertFalse(PermissionRegistry::canAccess('settings.themes.add', MemberRole::ADMIN));
    }

    public function test_an_admin_cannot_reach_theme_install_or_switch(): void
    {
        $admin = Member::factory()->create([
            'account_name' => 'siteadmin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
        ]);
        $this->actingAs($admin, 'member');

        $this->get(route('admin.settings.themes.index'))->assertForbidden();
        $this->post(route('admin.settings.themes.install'), ['directory' => 'Anything'])->assertForbidden();
        $this->post(route('admin.settings.themes.switch', ['id' => 1]))->assertForbidden();
        $this->post(route('admin.settings.themes.delete'), ['directory' => 'Anything'])->assertForbidden();
    }
}
