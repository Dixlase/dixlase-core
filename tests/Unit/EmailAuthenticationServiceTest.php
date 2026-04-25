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

namespace Tests\Unit;

use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Services\EmailAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailAuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmailAuthenticationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = app(EmailAuthenticationService::class);
    }

    /**
     * メール認証が利用可能かどうかのチェック
     */
    public function test_is_available_returns_true_when_mail_configured(): void
    {
        $result = $this->service->isAvailable();

        $this->assertTrue($result);
    }

    /**
     * コード検証 - 正しいコード
     */
    public function test_validate_code_returns_true_for_valid_code(): void
    {
        $member = Member::factory()->create();
        $code = '123456';

        // トークンを直接作成
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->service->validateCode($member, $code);

        $this->assertTrue($result);
    }

    /**
     * コード検証 - 不正なコード
     */
    public function test_validate_code_returns_false_for_invalid_code(): void
    {
        $member = Member::factory()->create();

        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->service->validateCode($member, '000000');

        $this->assertFalse($result);
    }

    /**
     * コード検証 - 期限切れコード
     */
    public function test_validate_code_returns_false_for_expired_code(): void
    {
        $member = Member::factory()->create();
        $code = '123456';

        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make($code),
            'expires_at' => now()->subMinutes(5),
        ]);

        $result = $this->service->validateCode($member, $code);

        $this->assertFalse($result);
    }

    /**
     * 統計情報の取得
     */
    public function test_get_stats_returns_correct_statistics(): void
    {
        $member = Member::factory()->create();

        // アクティブなトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(5),
        ]);

        // 期限切れトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('222222'),
            'expires_at' => now()->subMinutes(5),
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(2, $stats['total_tokens']);
        $this->assertEquals(1, $stats['active_tokens']);
        $this->assertEquals(1, $stats['expired_tokens']);
    }

    /**
     * 期限切れトークンのクリーンアップ
     */
    public function test_cleanup_expired_tokens(): void
    {
        $member = Member::factory()->create();

        // アクティブなトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(5),
        ]);

        // 期限切れトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('222222'),
            'expires_at' => now()->subMinutes(5),
        ]);

        $deleted = $this->service->cleanupExpiredTokens();

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, MemberTwoFaToken::count());
    }

    /**
     * アクティブなトークンの取得
     */
    public function test_get_active_token_returns_valid_token(): void
    {
        $member = Member::factory()->create();

        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $token = $this->service->getActiveToken($member);

        $this->assertNotNull($token);
        $this->assertEquals($member->id, $token->member_id);
    }

    /**
     * アクティブなトークンがない場合
     */
    public function test_get_active_token_returns_null_when_no_active_token(): void
    {
        $member = Member::factory()->create();

        // 期限切れトークンのみ
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(5),
        ]);

        $token = $this->service->getActiveToken($member);

        $this->assertNull($token);
    }

    /**
     * ユーザートークンの無効化
     */
    public function test_revoke_user_tokens(): void
    {
        $member = Member::factory()->create();

        // 複数のトークンを作成
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(5),
        ]);

        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('222222'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $deleted = $this->service->revokeUserTokens($member);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(0, MemberTwoFaToken::where('member_id', $member->id)->count());
    }

    /**
     * コード再送信可能かどうかのチェック - 可能
     */
    public function test_can_resend_code_returns_true_when_enough_time_passed(): void
    {
        $member = Member::factory()->create();

        // 2分前のトークン
        $token = MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(3),
        ]);
        $token->created_at = now()->subMinutes(2);
        $token->save();

        $result = $this->service->canResendCode($member, 1);

        $this->assertTrue($result);
    }

    /**
     * コード再送信可能かどうかのチェック - 不可
     */
    public function test_can_resend_code_returns_false_when_too_soon(): void
    {
        $member = Member::factory()->create();

        // 直近のトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->service->canResendCode($member, 1);

        $this->assertFalse($result);
    }

    /**
     * トークンがない場合は再送信可能
     */
    public function test_can_resend_code_returns_true_when_no_token(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->canResendCode($member, 1);

        $this->assertTrue($result);
    }

    /**
     * 再送信制限に引っかかった場合はnullを返す
     */
    public function test_resend_code_returns_null_when_limited(): void
    {
        $member = Member::factory()->create();

        // 直近のトークン
        MemberTwoFaToken::create([
            'member_id' => $member->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->service->resendCode($member, 'admin', 1);

        $this->assertNull($result);
    }

    /**
     * 他のユーザーのトークンは影響を受けない
     */
    public function test_revoke_user_tokens_does_not_affect_other_users(): void
    {
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();

        MemberTwoFaToken::create([
            'member_id' => $member1->id,
            'code' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(5),
        ]);

        MemberTwoFaToken::create([
            'member_id' => $member2->id,
            'code' => Hash::make('222222'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->service->revokeUserTokens($member1);

        $this->assertEquals(0, MemberTwoFaToken::where('member_id', $member1->id)->count());
        $this->assertEquals(1, MemberTwoFaToken::where('member_id', $member2->id)->count());
    }
}
