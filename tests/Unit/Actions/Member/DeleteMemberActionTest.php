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

namespace Tests\Unit\Actions\Member;

use App\Actions\Member\DeleteMemberAction;
use App\Actors\SystemActor;
use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteMemberActionTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberWithId(int $id, array $overrides = []): Member
    {
        $member = Member::create(array_merge([
            'account_name' => 'member'.$id,
            'display_name' => 'Member '.$id,
            'email' => "member{$id}@example.com",
            'password' => bcrypt('Password123!'),
            'role' => MemberRole::ADMIN->value,
            'status' => 1,
            'email_verified_at' => now(),
        ], $overrides));

        // id は $fillable に含まれないため、作成後に直接更新
        if ($member->id !== $id) {
            DB::table('members')->where('id', $member->id)->update(['id' => $id]);
            $member = Member::find($id);
        }

        return $member;
    }

    public function test_deletes_member_successfully(): void
    {
        Audit::shouldReceive('log')->once();

        // ID=1 は初期管理者として保護されるため、プレースホルダーを作成
        $this->createMemberWithId(1);
        $member = $this->createMemberWithId(2, ['display_name' => 'Delete Me']);
        $action = new DeleteMemberAction($member);

        $result = $action->execute(new SystemActor(), []);

        $this->assertTrue($result->success);
        $this->assertSoftDeleted('members', ['id' => $member->id]);
    }

    public function test_prevents_deletion_of_initial_admin(): void
    {
        Audit::shouldReceive('log')->never();

        // ID=1 の初期管理者を作成
        $member = $this->createMemberWithId(1, ['account_name' => 'admin1']);

        $action = new DeleteMemberAction($member);
        $result = $action->execute(new SystemActor(), []);

        $this->assertFalse($result->success);
        $this->assertSame('Cannot delete the initial admin account.', $result->message);
        $this->assertDatabaseHas('members', ['id' => 1]);
    }

    public function test_returns_correct_target_info(): void
    {
        Audit::shouldReceive('log')->once();

        // ID=1 プレースホルダー + 削除対象メンバー
        $this->createMemberWithId(1);
        $member = $this->createMemberWithId(2, ['display_name' => 'Target User']);
        $action = new DeleteMemberAction($member);

        $result = $action->execute(new SystemActor(), []);

        $this->assertSame(Member::class, $result->targetType);
        $this->assertSame('Target User', $result->targetLabel);
    }
}
