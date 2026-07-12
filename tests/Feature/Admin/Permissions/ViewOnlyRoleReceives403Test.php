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

namespace Tests\Feature\Admin\Permissions;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\RolePermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Feature-level pin of Phase 3 P0 + P1: a member whose role has view but
 * NOT edit permission on a menu MUST receive 403 on every state-changing
 * POST / PATCH / DELETE route for that menu.
 *
 * The unit tests already cover the middleware wiring
 * (`ViewOnlyServerSideGuardsTest`, `AdminMenuKeyConsistencyTest`,
 * `CheckMenuAccessSharesEditableTest`) and the UI-layer dim
 * (`AdminActionButtonsViewOnlyTest`). This test proves the two rails
 * meet the request pipeline correctly: an actual HTTP request from a
 * view-only user gets rejected with 403 before the controller runs,
 * so the P0/P1 fixes hold at the end-to-end HTTP level.
 *
 * Setup: an ADMIN-role member is granted view-permission but denied
 * edit-permission on the target menu via `RolePermissionOverride`
 * (access_roles = SUPER_ADMIN, view_roles = ADMIN). Under those
 * overrides:
 *   - CheckMenuAccess passes (ADMIN >= view_roles)
 *   - CheckMenuEdit rejects (ADMIN < access_roles) → 403
 * which is the exact "view-only" scenario the P0/P1 fixes were meant
 * to enforce.
 */
class ViewOnlyRoleReceives403Test extends TestCase
{
    use RefreshDatabase;

    private Member $viewOnlyAdmin;

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

        // ADMIN-role member — has ADMIN privileges globally, but the
        // per-menu overrides below carve out view-only scopes for the
        // specific menus each test targets.
        $this->viewOnlyAdmin = Member::factory()->create([
            'account_name' => 'view-only-admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
        ]);
    }

    /**
     * Grant $this->viewOnlyAdmin (ADMIN) view but not edit for $menuKey.
     * access_roles = SUPER_ADMIN (10) means only super_admin can edit;
     * view_roles = ADMIN (9) means ADMIN and above can view.
     */
    private function makeMenuViewOnlyForAdmin(string $menuKey): void
    {
        RolePermissionOverride::create([
            'source_type' => 'core',
            'source_id' => null,
            'menu_key' => $menuKey,
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
            'updated_by' => $this->viewOnlyAdmin->id,
        ]);
    }

    // =========================================================================
    // P0 — routes that PR #135 added `check.menu.edit` guard to
    // =========================================================================

    public function test_view_only_admin_cannot_store_members(): void
    {
        $this->makeMenuViewOnlyForAdmin('members.create_edit');

        $response = $this->actingAs($this->viewOnlyAdmin, 'member')
            ->post(route('admin.members.store'), [
                'account_name' => 'attacker',
                'display_name' => 'Attacker',
                'email' => 'attacker@example.com',
                'password' => 'password123',
                'role' => MemberRole::SUPER_ADMIN->value,
            ]);

        $response->assertForbidden();
        // Belt-and-braces: no attacker row landed in the members table.
        // If this ever fails while the status assertion passes, it means
        // the store action ran despite the 403 response — a controller
        // ordering bug worth catching loudly.
        $this->assertDatabaseMissing('members', ['account_name' => 'attacker']);
    }

    public function test_view_only_admin_cannot_update_members(): void
    {
        $this->makeMenuViewOnlyForAdmin('members.create_edit');

        // A separate member to try to edit — never our own account, so
        // the 403 cannot be attributed to "editing yourself" quirks.
        $target = Member::factory()->create([
            'account_name' => 'target',
            'display_name' => 'Target',
            'role' => MemberRole::EDITOR,
        ]);

        $response = $this->actingAs($this->viewOnlyAdmin, 'member')
            ->post(route('admin.members.update', ['member' => $target->id]), [
                'account_name' => 'target',
                'display_name' => 'Renamed',
                'role' => MemberRole::EDITOR->value,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('members', [
            'id' => $target->id,
            'display_name' => 'Target',
        ]);
    }

    public function test_view_only_admin_cannot_run_mail_test_routes(): void
    {
        $this->makeMenuViewOnlyForAdmin('settings.base.mail');

        // All three mail-test endpoints share settings.base.mail — one
        // override configures them together. Each was `check.menu.access`
        // only pre-PR-#135; all three should now 403 for a view-only
        // ADMIN.
        $routes = [
            'admin.settings.base.mail.test-mail',
            'admin.settings.base.mail.test-connection',
            'admin.settings.base.mail.clear-test-session',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($this->viewOnlyAdmin, 'member')
                ->post(route($routeName));

            $response->assertForbidden(sprintf(
                'Route %s must reject a view-only ADMIN with 403 (PR #135 guard).',
                $routeName,
            ));
        }
    }

    public function test_view_only_admin_cannot_run_captcha_test_routes(): void
    {
        $this->makeMenuViewOnlyForAdmin('settings.security.captcha');

        // Same shape as the mail-test suite — both captcha test endpoints
        // share settings.security.captcha and were access-only pre-#135.
        $routes = [
            'admin.settings.security.captcha.validate-widget',
            'admin.settings.security.captcha.clear-test',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($this->viewOnlyAdmin, 'member')
                ->post(route($routeName));

            $response->assertForbidden(sprintf(
                'Route %s must reject a view-only ADMIN with 403 (PR #135 guard).',
                $routeName,
            ));
        }
    }

    // =========================================================================
    // P1 — routes where PR #137 aligned the GET menu key to the sibling
    //      POST edit key
    // =========================================================================

    public function test_view_only_admin_cannot_update_media_settings(): void
    {
        $this->makeMenuViewOnlyForAdmin('media.settings');

        $response = $this->actingAs($this->viewOnlyAdmin, 'member')
            ->post(route('admin.media.settings.update'), [
                'max_upload_size_kb' => 99999,
            ]);

        $response->assertForbidden();
    }

    public function test_view_only_admin_cannot_update_member_roles(): void
    {
        $this->makeMenuViewOnlyForAdmin('members.roles');

        $response = $this->actingAs($this->viewOnlyAdmin, 'member')
            ->post(route('admin.members.roles.update'), [
                'permissions' => [],
            ]);

        $response->assertForbidden();
    }
}
