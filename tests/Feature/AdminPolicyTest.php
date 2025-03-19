<?php

/**
 * This file is part of MySoftware.
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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Member;
use App\Policies\AdminPolicy;

class AdminPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function super_admin_can_access_viewer()
    {
        // 特権管理者を作成
        $member = Member::factory()->create(['role' => 'super_admin']);
        $policy = new AdminPolicy();

        // 特権管理者は viewer の権限を持つ
        $this->assertTrue($policy->viewer($member));
    }

    /** @test */
    public function manager_can_access_receptionist()
    {
        // 管理者を作成
        $member = Member::factory()->create(['role' => 'manager']);
        $policy = new AdminPolicy();

        // 管理者は receptionist の権限を持つ
        $this->assertTrue($policy->receptionist($member));
    }

    /** @test */
    public function editor_cannot_access_manager()
    {
        // 編集者を作成
        $member = Member::factory()->create(['role' => 'editor']);
        $policy = new AdminPolicy();

        // 編集者は manager の権限を持たない
        $this->assertFalse($policy->manager($member));
    }

    /** @test */
    public function receptionist_cannot_access_editor()
    {
        // 受付を作成
        $member = Member::factory()->create(['role' => 'receptionist']);
        $policy = new AdminPolicy();

        // 受付は editor の権限を持たない
        $this->assertFalse($policy->editor($member));
    }
}
