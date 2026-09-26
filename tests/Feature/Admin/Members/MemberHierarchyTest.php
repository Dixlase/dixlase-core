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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Pins the member hierarchy on every route that writes to another member.
 *
 * Before this, the member form and the security operations only checked that
 * the actor held the members menu (ADMIN). An ADMIN could post role=10 on its
 * own account and become SUPER_ADMIN, or change the initial admin's password,
 * email or status, or revoke a SUPER_ADMIN's passkeys.
 */
class MemberHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    private Member $admin;

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

        // The initial admin is identified by id 1. Pin it explicitly: the
        // auto-increment is not reset between tests on every CI database.
        $this->superAdmin = Member::factory()->create([
            'id' => 1,
            'account_name' => 'owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);

        $this->admin = Member::factory()->create([
            'account_name' => 'staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function formFor(Member $member, array $overrides = []): array
    {
        return array_merge([
            'account_name' => $member->account_name,
            'display_name' => $member->display_name,
            'email' => $member->email,
            'role' => $member->role->value,
            'appearance' => 0,
            'status' => 1,
        ], $overrides);
    }

    /**
     * A password that meets the policy and cannot appear in a breach corpus,
     * so a rejection can only come from the hierarchy check.
     */
    /**
     * @return array{password: string, password_confirmation: string}
     */
    private function strongPassword(): array
    {
        $password = 'Hx9!'.bin2hex(random_bytes(8)).'Qz#';

        return ['password' => $password, 'password_confirmation' => $password];
    }

    public function test_initial_admin_is_the_super_admin_fixture(): void
    {
        $this->assertSame(1, $this->superAdmin->id);
    }

    public function test_admin_cannot_promote_itself_to_super_admin(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.members.update', ['member' => $this->admin->id]),
                $this->formFor($this->admin, ['role' => MemberRole::SUPER_ADMIN->value]))
            ->assertSessionHasErrors('role');

        $this->assertSame(MemberRole::ADMIN, $this->admin->fresh()->role);
    }

    public function test_admin_cannot_change_the_super_admin_password(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.members.update', ['member' => $this->superAdmin->id]),
                $this->formFor($this->superAdmin, ['role' => null] + $this->strongPassword()))
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $this->superAdmin->fresh()->password));
    }

    public function test_admin_cannot_create_a_super_admin(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.members.store'), [
                'account_name' => 'intruder',
                'email' => 'intruder@example.com',
                'email_confirmation' => 'intruder@example.com',
                'role' => MemberRole::SUPER_ADMIN->value,
                'appearance' => 0,
                'status' => 1,
            ] + $this->strongPassword())
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('members', ['account_name' => 'intruder']);
    }

    public function test_admin_can_still_edit_a_peer_below_super_admin(): void
    {
        $editor = Member::factory()->create([
            'account_name' => 'writer',
            'email' => 'writer@example.com',
            'role' => MemberRole::EDITOR,
            'status' => 1,
        ]);

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.members.update', ['member' => $editor->id]),
                $this->formFor($editor, ['display_name' => 'Renamed']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('Renamed', $editor->fresh()->display_name);
    }

    public function test_member_cannot_deactivate_itself(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.members.update', ['member' => $this->admin->id]),
                $this->formFor($this->admin, ['status' => 0]))
            ->assertSessionHasErrors('status');

        $this->assertSame(1, (int) $this->admin->fresh()->status->value);
    }

    public function test_super_admin_cannot_demote_the_initial_admin(): void
    {
        $second = Member::factory()->create([
            'account_name' => 'coowner',
            'email' => 'coowner@example.com',
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);

        $this->actingAs($second, 'member')
            ->post(route('admin.members.update', ['member' => $this->superAdmin->id]),
                $this->formFor($this->superAdmin, ['role' => MemberRole::EDITOR->value]))
            ->assertSessionHasErrors('role');

        $this->assertSame(MemberRole::SUPER_ADMIN, $this->superAdmin->fresh()->role);
    }

    public function test_super_admin_can_promote_an_admin(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.members.update', ['member' => $this->admin->id]),
                $this->formFor($this->admin, ['role' => MemberRole::SUPER_ADMIN->value]))
            ->assertSessionHasNoErrors();

        $this->assertSame(MemberRole::SUPER_ADMIN, $this->admin->fresh()->role);
    }

    public function test_admin_cannot_run_security_operations_on_a_super_admin(): void
    {
        $member = ['member' => $this->superAdmin->id];

        $this->actingAs($this->admin, 'member')->post(route('admin.members.force-logout', $member))->assertForbidden();
        $this->actingAs($this->admin, 'member')->post(route('admin.members.unlock-lockout', $member))->assertForbidden();
        $this->actingAs($this->admin, 'member')->post(route('admin.members.send-verification-email', $member))->assertForbidden();
        $this->actingAs($this->admin, 'member')->delete(route('admin.members.recovery-codes.revoke', $member))->assertForbidden();
        $this->actingAs($this->admin, 'member')
            ->delete(route('admin.members.passkey.revoke', $member + ['credentialId' => 'all']))
            ->assertForbidden();

        $this->assertNotNull($this->superAdmin->fresh()->email_verified_at);
    }

    public function test_force_logout_all_skips_members_ranked_above_the_actor(): void
    {
        foreach ([$this->superAdmin, $this->admin] as $i => $member) {
            DB::table('members_sessions')->insert([
                'id' => 'session-'.$i,
                'member_id' => $member->id,
                'payload' => '',
                'last_activity' => time(),
            ]);
        }
        $editor = Member::factory()->create(['account_name' => 'writer2', 'email' => 'writer2@example.com', 'role' => MemberRole::EDITOR]);
        DB::table('members_sessions')->insert(['id' => 'session-editor', 'member_id' => $editor->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->admin, 'member')->post(route('admin.members.force-logout-all'));

        $this->assertDatabaseHas('members_sessions', ['member_id' => $this->superAdmin->id]);
        $this->assertDatabaseMissing('members_sessions', ['member_id' => $editor->id]);
    }
}
