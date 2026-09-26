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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * account_name is a login identifier and every lookup takes the first match,
 * so two live members must never share one: a duplicate would shadow the
 * other member's login (or be shadowed by it).
 */
class AccountNameUniquenessTest extends TestCase
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
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    public function test_a_member_cannot_rename_themselves_to_another_members_account_name(): void
    {
        $this->actingAs($this->staff, 'member');

        $this->post(route('admin.profile.basic.update'), [
            'account_name' => 'owner',
            'email' => 'staff@example.com',
        ])->assertSessionHasErrors('account_name');

        $this->assertSame('staff', $this->staff->fresh()?->getAttribute('account_name'));
    }

    public function test_keeping_ones_own_account_name_still_validates(): void
    {
        $this->actingAs($this->staff, 'member');

        $this->post(route('admin.profile.basic.update'), [
            'account_name' => 'staff',
            'email' => 'staff@example.com',
        ])->assertSessionDoesntHaveErrors('account_name');
    }

    public function test_the_name_of_a_deleted_member_can_be_reused(): void
    {
        $this->staff->delete();
        $this->actingAs($this->owner, 'member');

        $this->post(route('admin.profile.basic.update'), [
            'account_name' => 'staff',
            'email' => 'owner@example.com',
        ])->assertSessionDoesntHaveErrors('account_name');
    }
}
