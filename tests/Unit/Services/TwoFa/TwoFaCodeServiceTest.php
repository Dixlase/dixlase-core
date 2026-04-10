<?php

namespace Tests\Unit\Services\TwoFa;

use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Services\TwoFa\TwoFaCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFaCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TwoFaCodeService $service;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TwoFaCodeService::class);
        $this->member = Member::factory()->create();
    }

    public function test_generate_returns_6_digit_code(): void
    {
        $code = $this->service->generate($this->member);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_generate_stores_hashed_code_in_db(): void
    {
        $code = $this->service->generate($this->member);

        $token = MemberTwoFaToken::where('member_id', $this->member->id)->first();

        $this->assertNotNull($token);
        $this->assertNotEquals($code, $token->code);
        $this->assertTrue(Hash::check($code, $token->code));
    }

    public function test_generate_deletes_old_codes(): void
    {
        $this->service->generate($this->member);
        $this->service->generate($this->member);

        $count = MemberTwoFaToken::where('member_id', $this->member->id)->count();

        $this->assertEquals(1, $count);
    }

    public function test_generate_sets_expiration(): void
    {
        $this->service->generate($this->member, 10);

        $token = MemberTwoFaToken::where('member_id', $this->member->id)->first();

        $this->assertNotNull($token->expires_at);
        // 有効期限が未来であること
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_validate_returns_true_for_correct_code(): void
    {
        $code = $this->service->generate($this->member);

        $this->assertTrue($this->service->validate($this->member, $code));
    }

    public function test_validate_returns_false_for_wrong_code(): void
    {
        $this->service->generate($this->member);

        $this->assertFalse($this->service->validate($this->member, '000000'));
    }

    public function test_validate_returns_false_for_expired_code(): void
    {
        $code = $this->service->generate($this->member, 5);

        // トークンの有効期限を過去に変更
        MemberTwoFaToken::where('member_id', $this->member->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($this->service->validate($this->member, $code));
    }

    public function test_validate_deletes_token_on_success(): void
    {
        $code = $this->service->generate($this->member);

        $this->service->validate($this->member, $code);

        $this->assertEquals(0, MemberTwoFaToken::where('member_id', $this->member->id)->count());
    }

    public function test_validate_returns_false_when_no_token(): void
    {
        $this->assertFalse($this->service->validate($this->member, '123456'));
    }

    public function test_has_valid_code(): void
    {
        $this->assertFalse($this->service->hasValidCode($this->member));

        $this->service->generate($this->member);

        $this->assertTrue($this->service->hasValidCode($this->member));
    }

    public function test_has_valid_code_false_when_expired(): void
    {
        $this->service->generate($this->member);

        MemberTwoFaToken::where('member_id', $this->member->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($this->service->hasValidCode($this->member));
    }

    public function test_revoke_all_deletes_all_tokens(): void
    {
        $this->service->generate($this->member);

        $deleted = $this->service->revokeAll($this->member);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(0, MemberTwoFaToken::where('member_id', $this->member->id)->count());
    }

    public function test_codes_are_isolated_per_member(): void
    {
        $otherMember = Member::factory()->create();

        $code1 = $this->service->generate($this->member);
        $code2 = $this->service->generate($otherMember);

        // 他のメンバーのコードでは検証できない
        $this->assertFalse($this->service->validate($this->member, $code2));
        $this->assertFalse($this->service->validate($otherMember, $code1));
    }
}
