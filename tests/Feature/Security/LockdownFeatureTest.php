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

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Models\LockdownStatus;
use App\Models\Member;
use App\Services\LockdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 緊急ロックダウン機能テスト
 *
 * ロックダウン発動時にHTTPレベルでアクセスが遮断されることを保証
 */
class LockdownFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        LockdownService::clearCache();
    }

    protected function tearDown(): void
    {
        LockdownService::deactivateAll();
        LockdownService::clearCache();
        parent::tearDown();
    }

    // ========================================
    // 基本的なロックダウン動作
    // ========================================

    public function test_admin_routes_accessible_when_no_lockdown(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_full_lockdown_blocks_admin_routes(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident'
        );

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(503);
    }

    public function test_admin_lockdown_blocks_admin_routes(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_ADMIN,
            reason: 'Admin maintenance'
        );

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(503);
    }

    public function test_login_lockdown_blocks_login_page(): void
    {
        LockdownService::activate(
            type: LockdownStatus::TYPE_LOGIN,
            reason: 'Brute force attack'
        );

        $response = $this->get(route('admin.login'));

        $response->assertStatus(503);
    }

    // ========================================
    // SUPER_ADMIN例外テスト
    // ========================================

    public function test_super_admin_can_access_during_full_lockdown(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident'
        );

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_during_admin_lockdown(): void
    {
        $superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_ADMIN,
            reason: 'Admin maintenance'
        );

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    // ========================================
    // 許可リストテスト
    // ========================================

    public function test_allowed_ip_can_access_during_lockdown(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident',
            allowedIps: ['127.0.0.1']
        );

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_allowed_member_can_access_during_lockdown(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident',
            allowedMembers: [$member->id]
        );

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_non_allowed_member_blocked_during_lockdown(): void
    {
        $allowedMember = Member::factory()->create(['role' => MemberRole::ADMIN]);
        $blockedMember = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident',
            allowedMembers: [$allowedMember->id]
        );

        $response = $this->actingAs($blockedMember)->get(route('admin.dashboard'));

        $response->assertStatus(503);
    }

    // ========================================
    // タイプ別ロックダウンテスト
    // ========================================

    public function test_api_lockdown_does_not_block_admin_routes(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_API,
            reason: 'API abuse'
        );

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_login_lockdown_does_not_block_authenticated_admin(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_LOGIN,
            reason: 'Brute force attack'
        );

        // 既にログイン済みのユーザーはダッシュボードにアクセス可能
        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    // ========================================
    // ロックダウン解除テスト
    // ========================================

    public function test_routes_accessible_after_lockdown_deactivation(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident'
        );

        // ロックダウン中はブロック
        $response = $this->actingAs($member)->get(route('admin.dashboard'));
        $response->assertStatus(503);

        // ロックダウン解除
        LockdownService::deactivate();

        // 解除後はアクセス可能
        $response = $this->actingAs($member)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    // ========================================
    // APIレスポンステスト
    // ========================================

    public function test_api_request_returns_json_during_lockdown(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident'
        );

        $response = $this->actingAs($member)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(503);
        $response->assertJson([
            'error' => 'lockdown',
        ]);
    }

    // ========================================
    // 自動解除テスト
    // ========================================

    public function test_auto_release_works_after_timeout(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        LockdownService::activate(
            type: LockdownStatus::TYPE_FULL,
            reason: 'Security incident',
            autoReleaseMinutes: 1
        );

        // auto_release_atを過去に設定
        LockdownStatus::active()->update([
            'auto_release_at' => now()->subMinutes(5)
        ]);
        LockdownService::clearCache();

        // 自動解除チェックを実行
        LockdownService::checkAutoRelease();

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
