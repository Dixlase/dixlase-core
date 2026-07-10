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

namespace Tests\Unit\Middleware;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckMenuAccess;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Pins the "share menuEditable + menuKey with downstream views" contract that
 * <x-admin.save-button>, <x-admin.delete-button>, and <x-admin.danger-zone>
 * rely on to hide themselves for view-only users.
 *
 * The middleware calls a private edit-permission check (AdminHelper::canEditMenu)
 * during a request that already passed the access check. We assert the derived
 * boolean lands on the View facade so admin components see it via
 * `$menuEditable ?? true`.
 */
class CheckMenuAccessSharesEditableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset any residual View::share state from prior tests.
        View::share('menuEditable', null);
        View::share('menuKey', null);
    }

    public function test_super_admin_gets_menu_editable_true_and_menu_key_shared(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);
        Auth::guard('member')->login($admin);

        $middleware = new CheckMenuAccess();
        $reached = false;
        $response = $middleware->handle(
            Request::create('/admin/settings/base/site', 'GET'),
            function () use (&$reached) {
                $reached = true;

                return response('OK');
            },
            'settings.base.site',
        );

        $this->assertTrue($reached, 'next handler must run when access check passes');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(View::shared('menuEditable'), 'SUPER_ADMIN must be flagged as editable');
        $this->assertSame('settings.base.site', View::shared('menuKey'));
    }

    public function test_dashboard_is_shared_as_view_only_for_any_authenticated_role(): void
    {
        // Dashboard is hardcoded view-only for every role in AdminHelper —
        // canAccessMenu returns true (dashboard is always accessible) while
        // canEditMenu returns false. This proves the middleware routes both
        // signals independently: passing next() AND sharing menuEditable=false.
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);
        Auth::guard('member')->login($editor);

        $middleware = new CheckMenuAccess();
        $reached = false;
        $middleware->handle(
            Request::create('/admin/dashboard', 'GET'),
            function () use (&$reached) {
                $reached = true;

                return response('OK');
            },
            'dashboard',
        );

        $this->assertTrue($reached, 'dashboard access must not be blocked');
        $this->assertFalse(
            View::shared('menuEditable'),
            'dashboard must be flagged as non-editable so save/delete/danger-zone components suppress themselves',
        );
        $this->assertSame('dashboard', View::shared('menuKey'));
    }

    public function test_profile_menu_is_shared_as_editable_for_own_settings(): void
    {
        // Profile is hardcoded editable for the owning user (str_starts_with
        // check in AdminHelper::canEditMenu). Different mechanism from
        // dashboard — this asserts that both hardcoded paths surface the
        // right menuEditable value.
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);
        Auth::guard('member')->login($editor);

        $middleware = new CheckMenuAccess();
        $middleware->handle(
            Request::create('/admin/profile', 'GET'),
            fn () => response('OK'),
            'profile',
        );

        $this->assertTrue(View::shared('menuEditable'));
        $this->assertSame('profile', View::shared('menuKey'));
    }

    public function test_shared_variables_do_not_leak_when_access_is_denied(): void
    {
        // Members table has no rows → AdminHelper::canAccessMenu returns
        // false (no user) → abort(403) fires before View::share happens.
        // The shared variable must remain at its setUp() null baseline so
        // subsequent requests do not see a stale "editable" verdict from
        // a different request context.
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware = new CheckMenuAccess();
        try {
            $middleware->handle(
                Request::create('/admin/settings/base/site', 'GET'),
                fn () => response('OK'),
                'settings.base.site',
            );
        } finally {
            $this->assertNull(View::shared('menuEditable'));
            $this->assertNull(View::shared('menuKey'));
        }
    }
}
