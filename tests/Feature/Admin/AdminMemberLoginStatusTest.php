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
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Regression coverage for the member status gate on admin login.
 *
 * Inactive members must never authenticate, even with valid credentials,
 * and a member without a password set must not be able to sign in through
 * the password form.
 */
class AdminMemberLoginStatusTest extends TestCase
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

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    public function test_inactive_member_cannot_log_in_even_with_correct_password(): void
    {
        $member = Member::create([
            'account_name' => 'inactiveadmin',
            'display_name' => 'Inactive Admin',
            'email' => 'inactive@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Inactive,
        ]);

        $response = $this->post(route('admin.login.store'), [
            'login' => $member->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('login');
        $this->assertGuest('member');
    }

    public function test_active_member_can_log_in(): void
    {
        $member = Member::create([
            'account_name' => 'activeadmin',
            'display_name' => 'Active Admin',
            'email' => 'active@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $response = $this->post(route('admin.login.store'), [
            'login' => $member->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_member_without_a_password_cannot_log_in(): void
    {
        $member = Member::create([
            'account_name' => 'nopasswordadmin',
            'display_name' => 'No Password Admin',
            'email' => 'nopassword@example.com',
            'password' => null,
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // The column accepts null so invited members can exist before setup.
        $this->assertNull($member->fresh()->password);

        $response = $this->post(route('admin.login.store'), [
            'login' => $member->email,
            'password' => 'any-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }
}
