<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Policies\AdminPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_guest(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN->value]);
        $policy = new AdminPolicy();

        $this->assertTrue($policy->guest($member));
    }

    public function test_admin_can_access_contributor(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN->value]);
        $policy = new AdminPolicy();

        $this->assertTrue($policy->contributor($member));
    }

    public function test_editor_cannot_access_admin(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::EDITOR->value]);
        $policy = new AdminPolicy();

        $this->assertFalse($policy->admin($member));
    }

    public function test_contributor_cannot_access_editor(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::CONTRIBUTOR->value]);
        $policy = new AdminPolicy();

        $this->assertFalse($policy->editor($member));
    }
}
