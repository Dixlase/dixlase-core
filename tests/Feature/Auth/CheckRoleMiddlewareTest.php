<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace Tests\Feature\Auth;

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * CheckRole ミドルウェアテスト
 *
 * ロールチェックがHTTPレベルで正しく機能することを保証
 */
class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ルートを登録
        Route::middleware(['web', 'auth:member', 'role:super_admin'])
            ->get('/test/role/super-admin', function () {
                return response('OK', 200);
            })->name('test.role.super-admin');

        Route::middleware(['web', 'auth:member', 'role:admin'])
            ->get('/test/role/admin', function () {
                return response('OK', 200);
            })->name('test.role.admin');

        Route::middleware(['web', 'auth:member', 'role:editor'])
            ->get('/test/role/editor', function () {
                return response('OK', 200);
            })->name('test.role.editor');

        Route::middleware(['web', 'auth:member', 'role:contributor'])
            ->get('/test/role/contributor', function () {
                return response('OK', 200);
            })->name('test.role.contributor');

        Route::middleware(['web', 'auth:member', 'role:guest'])
            ->get('/test/role/guest', function () {
                return response('OK', 200);
            })->name('test.role.guest');
    }

    // ========================================
    // SUPER_ADMIN ロールテスト
    // ========================================

    public function test_super_admin_can_access_super_admin_route(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/role/super-admin');

        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_super_admin_route(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $response = $this->actingAs($admin)->get('/test/role/super-admin');

        $response->assertStatus(403);
    }

    public function test_editor_cannot_access_super_admin_route(): void
    {
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/role/super-admin');

        $response->assertStatus(403);
    }

    // ========================================
    // ADMIN ロールテスト
    // ========================================

    public function test_super_admin_can_access_admin_route(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/role/admin');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $response = $this->actingAs($admin)->get('/test/role/admin');

        $response->assertStatus(200);
    }

    public function test_editor_cannot_access_admin_route(): void
    {
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/role/admin');

        $response->assertStatus(403);
    }

    public function test_contributor_cannot_access_admin_route(): void
    {
        $contributor = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);

        $response = $this->actingAs($contributor)->get('/test/role/admin');

        $response->assertStatus(403);
    }

    // ========================================
    // EDITOR ロールテスト
    // ========================================

    public function test_super_admin_can_access_editor_route(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/role/editor');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_editor_route(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $response = $this->actingAs($admin)->get('/test/role/editor');

        $response->assertStatus(200);
    }

    public function test_editor_can_access_editor_route(): void
    {
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/role/editor');

        $response->assertStatus(200);
    }

    public function test_contributor_cannot_access_editor_route(): void
    {
        $contributor = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);

        $response = $this->actingAs($contributor)->get('/test/role/editor');

        $response->assertStatus(403);
    }

    // ========================================
    // CONTRIBUTOR ロールテスト
    // ========================================

    public function test_contributor_can_access_contributor_route(): void
    {
        $contributor = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);

        $response = $this->actingAs($contributor)->get('/test/role/contributor');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_contributor_route(): void
    {
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $response = $this->actingAs($guest)->get('/test/role/contributor');

        $response->assertStatus(403);
    }

    // ========================================
    // GUEST ロールテスト
    // ========================================

    public function test_all_roles_can_access_guest_route(): void
    {
        $roles = [
            MemberRole::SUPER_ADMIN,
            MemberRole::ADMIN,
            MemberRole::EDITOR,
            MemberRole::CONTRIBUTOR,
            MemberRole::GUEST,
        ];

        foreach ($roles as $role) {
            $member = Member::factory()->create(['role' => $role]);

            $response = $this->actingAs($member)->get('/test/role/guest');

            $response->assertStatus(200, "Role {$role->value} should be able to access guest route");
        }
    }

    // ========================================
    // 認証テスト
    // ========================================

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/test/role/admin');

        $response->assertRedirect();
    }

    // ========================================
    // エッジケース
    // ========================================

    public function test_invalid_role_returns_500(): void
    {
        Route::middleware(['web', 'auth:member', 'role:invalid_role'])
            ->get('/test/role/invalid', function () {
                return response('OK', 200);
            });

        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/role/invalid');

        $response->assertStatus(500);
    }

    public function test_api_request_returns_json_on_403(): void
    {
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $response = $this->actingAs($guest)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/test/role/admin');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => __('common.errors.unauthorized'),
        ]);
    }

    // ========================================
    // ロール階層テスト
    // ========================================

    public function test_role_hierarchy_is_respected(): void
    {
        // ロール階層: SUPER_ADMIN > ADMIN > EDITOR > CONTRIBUTOR > GUEST
        $testCases = [
            ['role' => MemberRole::SUPER_ADMIN, 'can_access' => ['super_admin', 'admin', 'editor', 'contributor', 'guest']],
            ['role' => MemberRole::ADMIN, 'can_access' => ['admin', 'editor', 'contributor', 'guest']],
            ['role' => MemberRole::EDITOR, 'can_access' => ['editor', 'contributor', 'guest']],
            ['role' => MemberRole::CONTRIBUTOR, 'can_access' => ['contributor', 'guest']],
            ['role' => MemberRole::GUEST, 'can_access' => ['guest']],
        ];

        $routes = ['super_admin', 'admin', 'editor', 'contributor', 'guest'];

        foreach ($testCases as $testCase) {
            $member = Member::factory()->create(['role' => $testCase['role']]);

            foreach ($routes as $route) {
                $response = $this->actingAs($member)->get("/test/role/{$route}");

                if (in_array($route, $testCase['can_access'])) {
                    $this->assertEquals(
                        200,
                        $response->status(),
                        "Role {$testCase['role']->value} should be able to access {$route} route"
                    );
                } else {
                    $this->assertEquals(
                        403,
                        $response->status(),
                        "Role {$testCase['role']->value} should NOT be able to access {$route} route"
                    );
                }
            }
        }
    }
}
