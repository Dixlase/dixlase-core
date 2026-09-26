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

namespace Tests\Feature\Admin\Members;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Deactivating a member, or forcing them out, must end their access.
 *
 * Login checked the account state once. Afterwards nothing did: a deactivated
 * member kept a live session, and a remember-me cookie (always issued on
 * passkey login) let the guard log them back in on every visit, because the
 * remember token was only ever cycled on a password reset.
 */
class MemberDeactivationEndsAccessTest extends TestCase
{
    use RefreshDatabase;

    private Member $owner;

    private Member $staff;

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

        $this->owner = Member::factory()->create([
            'id' => 1,
            'account_name' => 'owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);

        $this->staff = Member::factory()->create([
            'account_name' => 'staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::EDITOR,
            'status' => 1,
            'remember_token' => 'remember-token-of-staff',
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    public function test_a_member_deactivated_mid_session_is_logged_out_on_the_next_request(): void
    {
        $this->actingAs($this->staff, 'member');

        // Deactivated behind their back (e.g. by an administrator elsewhere).
        DB::table('members')->where('id', $this->staff->id)->update(['status' => 0]);
        $this->staff->refresh();
        $this->actingAs($this->staff, 'member');

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->assertFalse(Auth::guard('member')->check());
        $this->assertNotSame('remember-token-of-staff', $this->staff->fresh()->remember_token);
    }

    public function test_force_logout_invalidates_the_remember_me_token(): void
    {
        $this->actingAs($this->owner, 'member')
            ->post(route('admin.members.force-logout', ['member' => $this->staff->id]));

        $this->assertNull($this->staff->fresh()->remember_token);
    }

    public function test_force_logout_all_invalidates_remember_me_tokens(): void
    {
        $this->actingAs($this->owner, 'member')->post(route('admin.members.force-logout-all'));

        $this->assertNull($this->staff->fresh()->remember_token);
    }

    public function test_deactivating_a_member_in_the_form_ends_their_sessions_and_token(): void
    {
        DB::table('members_sessions')->insert([
            'id' => 'staff-session',
            'member_id' => $this->staff->id,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->owner, 'member')
            ->post(route('admin.members.update', ['member' => $this->staff->id]), [
                'account_name' => 'staff',
                'email' => 'staff@example.com',
                'role' => MemberRole::EDITOR->value,
                'appearance' => 0,
                'status' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('members_sessions', ['member_id' => $this->staff->id]);
        $this->assertNull($this->staff->fresh()->remember_token);
    }
}
