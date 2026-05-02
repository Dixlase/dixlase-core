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

namespace Tests\Feature\Admin\Settings\Base;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * メンテナンスモード機能テスト
 */
class MaintenanceModeFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstallationReady::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    // ========================================
    // ミドルウェア：未ログイン時に503を返す
    // ========================================

    public function test_guest_sees_503_during_maintenance(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中です');

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('メンテナンス中です');
    }

    public function test_guest_does_not_see_503_when_maintenance_off(): void
    {
        SiteSetting::setValue('maintenance_mode', '0');

        $response = $this->get('/');

        $this->assertNotEquals(503, $response->getStatusCode());
    }

    // ========================================
    // ミドルウェア：管理者ログイン時はバイパス
    // ========================================

    public function test_authenticated_admin_does_not_see_503_during_maintenance(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中');

        $response = $this->actingAs($this->admin, 'member')
            ->get('/');

        $this->assertNotEquals(503, $response->getStatusCode());
    }

    // ========================================
    // ミドルウェア：スケジュール制御
    // ========================================

    public function test_guest_not_blocked_before_scheduled_maintenance_starts(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中');
        SiteSetting::setValue('maintenance_start_at', now()->addHour()->format('Y-m-d H:i:s'));

        $response = $this->get('/');

        $this->assertNotEquals(503, $response->getStatusCode());
    }

    public function test_guest_sees_503_after_scheduled_maintenance_starts(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中');
        SiteSetting::setValue('maintenance_start_at', now()->subHour()->format('Y-m-d H:i:s'));

        $response = $this->get('/');

        $response->assertStatus(503);
    }

    public function test_guest_not_blocked_after_maintenance_release_time(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中');
        SiteSetting::setValue('maintenance_auto_release', '1');
        SiteSetting::setValue('maintenance_release_at', now()->subHour()->format('Y-m-d H:i:s'));

        $response = $this->get('/');

        $this->assertNotEquals(503, $response->getStatusCode());
    }

    // ========================================
    // 管理画面パスはメンテナンス中でもバイパス
    // ========================================

    public function test_admin_path_not_blocked_during_maintenance(): void
    {
        SiteSetting::setValue('maintenance_mode', '1');
        SiteSetting::setValue('maintenance_message', 'メンテナンス中');

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard'));

        $this->assertNotEquals(503, $response->getStatusCode());
    }
}
