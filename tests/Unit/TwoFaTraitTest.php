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

use App\Enums\TwoFaMethod;
use App\Enums\AuthenticationMode;
use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFaTraitTest extends TestCase
{
    use RefreshDatabase;

    protected TestTwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TestTwoFactorService();
    }

    /**
     * コード生成のテスト
     */
    public function test_generate_two_factor_code_returns_six_digit_code(): void
    {
        $member = Member::factory()->create();

        $code = $this->service->generateTwoFaCode($member);

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    /**
     * コードがDBに保存されることのテスト
     */
    public function test_generate_two_factor_code_saves_to_database(): void
    {
        $member = Member::factory()->create();

        $code = $this->service->generateTwoFaCode($member);

        $this->assertDatabaseHas('members_two_fa_tokens', [
            'member_id' => $member->id,
        ]);

        $token = MemberTwoFaToken::where('member_id', $member->id)->first();
        $this->assertTrue(Hash::check($code, $token->code));
    }

    /**
     * 古いコードが削除されることのテスト
     */
    public function test_generate_two_factor_code_deletes_old_codes(): void
    {
        $member = Member::factory()->create();

        // 最初のコード生成
        $this->service->generateTwoFaCode($member);
        $this->assertEquals(1, MemberTwoFaToken::where('member_id', $member->id)->count());

        // 2回目のコード生成
        $this->service->generateTwoFaCode($member);
        $this->assertEquals(1, MemberTwoFaToken::where('member_id', $member->id)->count());
    }

    /**
     * 正しいコードの検証テスト
     */
    public function test_validate_two_factor_code_returns_true_for_valid_code(): void
    {
        $member = Member::factory()->create();
        $code = $this->service->generateTwoFaCode($member);

        $result = $this->service->validateTwoFaCode($member, $code);

        $this->assertTrue($result);
    }

    /**
     * 不正なコードの検証テスト
     */
    public function test_validate_two_factor_code_returns_false_for_invalid_code(): void
    {
        $member = Member::factory()->create();
        $this->service->generateTwoFaCode($member);

        $result = $this->service->validateTwoFaCode($member, '000000');

        $this->assertFalse($result);
    }

    /**
     * 期限切れコードの検証テスト
     */
    public function test_validate_two_factor_code_returns_false_for_expired_code(): void
    {
        $member = Member::factory()->create();
        $code = $this->service->generateTwoFaCode($member, 1);

        // トークンの有効期限を過去に設定
        MemberTwoFaToken::where('member_id', $member->id)
            ->update(['expires_at' => now()->subMinutes(5)]);

        $result = $this->service->validateTwoFaCode($member, $code);

        $this->assertFalse($result);
    }

    /**
     * コード検証後にトークンが削除されることのテスト
     */
    public function test_validate_two_factor_code_deletes_token_after_success(): void
    {
        $member = Member::factory()->create();
        $code = $this->service->generateTwoFaCode($member);

        $this->service->validateTwoFaCode($member, $code);

        $this->assertDatabaseMissing('members_two_fa_tokens', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 存在しないトークンの検証テスト
     */
    public function test_validate_two_factor_code_returns_false_when_no_token(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->validateTwoFaCode($member, '123456');

        $this->assertFalse($result);
    }

    /**
     * 2FA必須判定のテスト（無効設定）
     */
    public function test_requires_two_factor_returns_false_when_disabled(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->requiresTwoFa(
            $member,
            AuthenticationMode::Disabled->value,
            [TwoFaMethod::EMAIL->value]
        );

        $this->assertFalse($result);
    }

    /**
     * 2FA必須判定のテスト（常に有効）
     */
    public function test_requires_two_factor_returns_true_when_always(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->requiresTwoFa(
            $member,
            AuthenticationMode::Always->value,
            [TwoFaMethod::EMAIL->value]
        );

        $this->assertTrue($result);
    }

    /**
     * 2FA必須判定のテスト（認証方法が空）
     */
    public function test_requires_two_factor_returns_false_when_no_methods(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->requiresTwoFa(
            $member,
            AuthenticationMode::Always->value,
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 有効な認証方法取得のテスト
     */
    public function test_get_enabled_two_factor_methods_returns_default(): void
    {
        $methods = $this->service->getEnabledTwoFaMethods();

        $this->assertIsArray($methods);
        $this->assertContains(TwoFaMethod::EMAIL->value, $methods);
    }

    /**
     * カスタム有効期限のテスト
     */
    public function test_generate_two_factor_code_respects_custom_expiration(): void
    {
        $member = Member::factory()->create();
        $expireMinutes = 10;

        $this->service->generateTwoFaCode($member, $expireMinutes);

        $token = MemberTwoFaToken::where('member_id', $member->id)->first();

        // 有効期限が約10分後であることを確認（1分の誤差を許容）
        $expectedExpiry = now()->addMinutes($expireMinutes);
        $this->assertTrue(
            $token->expires_at->diffInMinutes($expectedExpiry) <= 1,
            'Token expiration should be approximately 10 minutes from now'
        );
    }
}

/**
 * テスト用のTwoFaTraitを使用するサービスクラス
 */
class TestTwoFactorService
{
    use \App\Traits\TwoFa\TwoFaUtilityTrait;

    protected function getSettingValue(string $key, $default = null)
    {
        return SecuritySetting::getValue($key, $default);
    }
}
