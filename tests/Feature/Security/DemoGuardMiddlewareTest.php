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

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * End-to-end check that the demo read-only guard (DemoGuard + ReadonlyRouteGuard,
 * wired into the web group) actually blocks a destructive admin POST while demo
 * mode is on. The critical property is that a shared demo account's password
 * cannot be changed by a visitor — the profile routes have no other gate.
 */
class DemoGuardMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Strip the unrelated admin gates, but NOT the demo guard under test.
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    private function member(MemberRole $role): Member
    {
        return Member::create([
            'account_name' => 'demo_'.$role->value,
            'display_name' => 'Demo',
            'email' => 'demo_'.$role->value.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
        ]);
    }

    public function test_profile_password_update_is_blocked_for_a_visitor_in_demo_mode(): void
    {
        config(['dixlase.demo_mode' => true]);

        $response = $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->postJson(route('admin.profile.password.update'), []);

        $response->assertStatus(403)
            ->assertJson(['error' => __('admin/demo.action_disabled')]);
    }

    public function test_a_blocked_non_json_post_redirects_back_with_an_error(): void
    {
        config(['dixlase.demo_mode' => true]);

        $response = $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->post(route('admin.profile.password.update'), []);

        $response->assertRedirect();
        $response->assertSessionHas('error', __('admin/demo.action_disabled'));
    }

    public function test_super_admin_bypasses_the_guard(): void
    {
        config(['dixlase.demo_mode' => true]);

        $response = $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->postJson(route('admin.profile.password.update'), []);

        // Not blocked — the request reaches the controller (validation 422 etc.).
        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_guard_is_inactive_when_demo_mode_is_off(): void
    {
        config(['dixlase.demo_mode' => false]);

        $response = $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->postJson(route('admin.profile.password.update'), []);

        $this->assertNotSame(403, $response->getStatusCode());
    }
}
