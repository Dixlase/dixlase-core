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
use App\Http\Middleware\CheckPermission;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * CheckPermission ミドルウェアテスト
 *
 * 権限チェックがHTTPレベルで正しく機能することを保証
 */
class CheckPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // テスト用ルートを登録
        Route::middleware(['web', 'auth:member', 'permission:members.view'])
            ->get('/test/permission/members-view', function () {
                return response('OK', 200);
            })->name('test.permission.members-view');

        Route::middleware(['web', 'auth:member', 'permission:members.create'])
            ->get('/test/permission/members-create', function () {
                return response('OK', 200);
            })->name('test.permission.members-create');

        Route::middleware(['web', 'auth:member', 'permission:members.delete'])
            ->get('/test/permission/members-delete', function () {
                return response('OK', 200);
            })->name('test.permission.members-delete');

        Route::middleware(['web', 'auth:member', 'permission:settings.system'])
            ->get('/test/permission/system-settings', function () {
                return response('OK', 200);
            })->name('test.permission.system-settings');

        Route::middleware(['web', 'auth:member', 'permission:members.view,members.create'])
            ->get('/test/permission/any-of', function () {
                return response('OK', 200);
            })->name('test.permission.any-of');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    // ========================================
    // 基本的な権限チェック
    // ========================================

    public function test_user_with_permission_can_access(): void
    {
        // SUPER_ADMINは全権限を持つ
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/permission/members-view');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function test_user_without_permission_gets_403(): void
    {
        // GUESTは限られた権限しか持たない
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $response = $this->actingAs($guest)->get('/test/permission/members-create');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/test/permission/members-view');

        // 未認証ユーザーはログインページにリダイレクト
        $response->assertRedirect();
    }

    // ========================================
    // ロール別権限テスト
    // ========================================

    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        // 通常の権限
        $response = $this->actingAs($superAdmin)->get('/test/permission/members-view');
        $response->assertStatus(200);

        // 危険な権限（members.delete）
        $response = $this->actingAs($superAdmin)->get('/test/permission/members-delete');
        $response->assertStatus(200);

        // システム設定
        $response = $this->actingAs($superAdmin)->get('/test/permission/system-settings');
        $response->assertStatus(200);
    }

    public function test_admin_has_limited_permissions(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);

        // 通常の権限は持つ
        $response = $this->actingAs($admin)->get('/test/permission/members-view');
        $response->assertStatus(200);

        // 危険な権限（members.delete）は持たない
        $response = $this->actingAs($admin)->get('/test/permission/members-delete');
        $response->assertStatus(403);
    }

    public function test_editor_cannot_access_system_settings(): void
    {
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/permission/system-settings');

        $response->assertStatus(403);
    }

    public function test_contributor_has_limited_permissions(): void
    {
        $contributor = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]);

        // メンバー閲覧は不可（Editor以上が必要）
        $response = $this->actingAs($contributor)->get('/test/permission/members-view');
        $response->assertStatus(403);

        // 作成は不可
        $response = $this->actingAs($contributor)->get('/test/permission/members-create');
        $response->assertStatus(403);
    }

    // ========================================
    // 複数権限（any of）テスト
    // ========================================

    public function test_any_of_permissions_allows_access_with_first_permission(): void
    {
        // members.viewを持つユーザー（Editor以上）
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/permission/any-of');

        // members.viewを持っているのでアクセス可能
        $response->assertStatus(200);
    }

    public function test_any_of_permissions_allows_access_with_second_permission(): void
    {
        // members.createを持つユーザー
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $response = $this->actingAs($editor)->get('/test/permission/any-of');

        // members.createを持っているのでアクセス可能
        $response->assertStatus(200);
    }

    public function test_any_of_permissions_denies_access_without_any(): void
    {
        // members.viewもmembers.createも持たないユーザー
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $response = $this->actingAs($guest)->get('/test/permission/any-of');

        $response->assertStatus(403);
    }

    // ========================================
    // エッジケース
    // ========================================

    public function test_middleware_handles_invalid_permission_gracefully(): void
    {
        Route::middleware(['web', 'auth:member', 'permission:invalid.permission'])
            ->get('/test/permission/invalid', function () {
                return response('OK', 200);
            });

        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->get('/test/permission/invalid');

        // 無効な権限は403を返す
        $response->assertStatus(403);
    }

    public function test_api_request_returns_json_on_403(): void
    {
        $guest = Member::factory()->create(['role' => MemberRole::GUEST]);

        $response = $this->actingAs($guest)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/test/permission/members-create');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => __('common.errors.unauthorized'),
        ]);
    }
}
