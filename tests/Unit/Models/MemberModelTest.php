<?php

namespace Tests\Unit\Models;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_be_created_with_factory(): void
    {
        $member = Member::factory()->create();

        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertNotNull($member->email);
        $this->assertNotNull($member->account_name);
    }

    public function test_password_is_hashed_automatically(): void
    {
        $member = Member::factory()->create(['password' => 'plain-text']);

        $member->refresh();
        $this->assertNotEquals('plain-text', $member->password);
        $this->assertTrue(Hash::check('plain-text', $member->password));
    }

    public function test_role_is_cast_to_enum(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $this->assertInstanceOf(MemberRole::class, $member->role);
        $this->assertEquals(MemberRole::ADMIN, $member->role);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $member = Member::factory()->create(['status' => MemberStatus::Active]);

        $this->assertInstanceOf(MemberStatus::class, $member->status);
        $this->assertEquals(MemberStatus::Active, $member->status);
    }

    public function test_member_can_be_created_with_super_admin_role(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        $this->assertEquals(MemberRole::SUPER_ADMIN, $member->role);
    }

    public function test_member_can_be_created_unverified(): void
    {
        $member = Member::factory()->create(['email_verified_at' => null]);

        $this->assertNull($member->email_verified_at);
    }

    public function test_member_can_be_created_inactive(): void
    {
        $member = Member::factory()->create(['status' => MemberStatus::Inactive]);

        $this->assertEquals(MemberStatus::Inactive, $member->status);
    }

    public function test_two_fa_mode_is_cast_to_enum(): void
    {
        $member = Member::factory()->create([
            'two_fa_mode' => AuthenticationMode::Always,
        ]);

        $this->assertInstanceOf(AuthenticationMode::class, $member->two_fa_mode);
    }

    public function test_sidebar_preferences_is_cast_to_array(): void
    {
        $prefs = ['collapsed' => true, 'order' => [1, 2, 3]];
        $member = Member::factory()->create(['sidebar_preferences' => $prefs]);

        $member->refresh();
        $this->assertIsArray($member->sidebar_preferences);
        $this->assertEquals($prefs, $member->sidebar_preferences);
    }

    public function test_two_fa_recovery_codes_relationship(): void
    {
        $member = Member::factory()->create();

        $this->assertCount(0, $member->twoFaRecoveryCodes);
    }

    public function test_two_fa_tokens_relationship(): void
    {
        $member = Member::factory()->create();

        $this->assertCount(0, $member->twoFaTokens);
    }

    public function test_soft_delete(): void
    {
        $member = Member::factory()->create();
        $id = $member->id;

        $member->delete();

        $this->assertSoftDeleted('members', ['id' => $id]);
        $this->assertNotNull(Member::withTrashed()->find($id));
    }
}
