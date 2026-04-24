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

namespace Tests\Feature\Admin\SafeMode;

use App\Enums\SafeMode;
use App\Services\SafeModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SafeModeService のテスト
 */
class SafeModeServiceTest extends TestCase
{
    use RefreshDatabase;

    private SafeModeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->service = new SafeModeService();
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_activate_stores_session_key(): void
    {
        $member = $this->createSuperAdmin();
        $this->actingAs($member, 'member');

        $this->service->activate(SafeMode::Csp);

        $this->assertTrue(session('safe_mode_csp'));
    }

    public function test_deactivate_removes_session_key(): void
    {
        $member = $this->createSuperAdmin();
        $this->actingAs($member, 'member');

        session(['safe_mode_csp' => true]);

        $this->service->deactivate(SafeMode::Csp);

        $this->assertNull(session('safe_mode_csp'));
    }

    public function test_is_active_returns_true_when_active(): void
    {
        session(['safe_mode_plugins' => true]);

        $this->assertTrue($this->service->isActive(SafeMode::Plugins));
    }

    public function test_is_active_returns_false_when_inactive(): void
    {
        $this->assertFalse($this->service->isActive(SafeMode::Plugins));
    }

    public function test_get_active_modes_returns_all_active(): void
    {
        session(['safe_mode_csp' => true, 'safe_mode_theme' => true]);

        $activeModes = $this->service->getActiveModes();

        $this->assertCount(2, $activeModes);
        $this->assertContains(SafeMode::Csp, $activeModes);
        $this->assertContains(SafeMode::Theme, $activeModes);
    }

    public function test_get_active_modes_returns_empty_when_none_active(): void
    {
        $activeModes = $this->service->getActiveModes();

        $this->assertCount(0, $activeModes);
    }

    public function test_has_any_active_returns_true_when_some_active(): void
    {
        session(['safe_mode_csp' => true]);

        $this->assertTrue($this->service->hasAnyActive());
    }

    public function test_has_any_active_returns_false_when_none_active(): void
    {
        $this->assertFalse($this->service->hasAnyActive());
    }

    public function test_deactivate_all_removes_all_modes(): void
    {
        $member = $this->createSuperAdmin();
        $this->actingAs($member, 'member');

        session([
            'safe_mode_csp' => true,
            'safe_mode_plugins' => true,
            'safe_mode_theme' => true,
        ]);

        $this->service->deactivateAll();

        $this->assertFalse($this->service->hasAnyActive());
    }

    private function createSuperAdmin(): \App\Models\Member
    {
        return \App\Models\Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
            'role' => \App\Enums\MemberRole::SUPER_ADMIN,
            'status' => \App\Enums\MemberStatus::Active,
            'two_fa_mode' => \App\Enums\AuthenticationMode::Disabled,
        ]);
    }
}
