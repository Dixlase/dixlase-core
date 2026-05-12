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
use App\Models\MemberTwoFaRecoveryCode;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFaRecoveryCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TwoFaRecoveryCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFaRecoveryCodeService();
    }

    /**
     * 回復コードの生成テスト
     */
    public function test_generate_creates_recovery_codes(): void
    {
        $member = Member::factory()->create();

        $codes = $this->service->generate($member);

        $this->assertIsArray($codes);
        $this->assertNotEmpty($codes);
        $this->assertGreaterThanOrEqual(1, count($codes));
        $this->assertLessThanOrEqual(5, count($codes));
    }

    /**
     * 生成されたコードが20桁であることのテスト
     */
    public function test_generated_codes_are_20_digits(): void
    {
        $member = Member::factory()->create();

        $codes = $this->service->generate($member);

        foreach ($codes as $code) {
            $this->assertEquals(20, strlen($code));
            $this->assertMatchesRegularExpression('/^\d{20}$/', $code);
        }
    }

    /**
     * 回復コードがDBに保存されることのテスト
     */
    public function test_generated_codes_are_saved_to_database(): void
    {
        $member = Member::factory()->create();

        $codes = $this->service->generate($member);

        $savedCodes = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->count();

        $this->assertEquals(count($codes), $savedCodes);
    }

    /**
     * 再生成時に古いコードが無効化されることのテスト
     */
    public function test_regenerate_disables_old_codes(): void
    {
        $member = Member::factory()->create();

        // 最初の生成
        $this->service->generate($member);
        $firstCount = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->count();

        // 再生成
        $this->service->generate($member);

        // 古いコードは無効化されている
        $disabledCount = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', true)
            ->count();

        $this->assertEquals($firstCount, $disabledCount);
    }

    /**
     * 正しいコードの検証テスト
     */
    public function test_validate_returns_true_for_valid_code(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        $result = $this->service->validate($member, $codes[0]);

        $this->assertTrue($result);
    }

    /**
     * 不正なコードの検証テスト
     */
    public function test_validate_returns_false_for_invalid_code(): void
    {
        $member = Member::factory()->create();
        $this->service->generate($member);

        $result = $this->service->validate($member, '00000000000000000000');

        $this->assertFalse($result);
    }

    /**
     * 使用済みコードの検証テスト
     */
    public function test_validate_returns_false_for_used_code(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        // 1回目の使用
        $this->service->validate($member, $codes[0]);

        // 2回目の使用（同じコード）
        $result = $this->service->validate($member, $codes[0]);

        $this->assertFalse($result);
    }

    /**
     * 無効化されたコードの検証テスト
     */
    public function test_validate_returns_false_for_disabled_code(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        // コードを無効化
        MemberTwoFaRecoveryCode::where('member_id', $member->id)->update(['disabled' => true]);

        $result = $this->service->validate($member, $codes[0]);

        $this->assertFalse($result);
    }

    /**
     * ハイフン付きコードの検証テスト
     */
    public function test_validate_accepts_code_with_hyphens(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        // ハイフン付きでフォーマット
        $formattedCode = $this->service->formatCode($codes[0]);

        $result = $this->service->validate($member, $formattedCode);

        $this->assertTrue($result);
    }

    /**
     * 残りコード数の取得テスト
     */
    public function test_get_remaining_count(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        $remaining = $this->service->getRemainingCount($member);

        $this->assertEquals(count($codes), $remaining);
    }

    /**
     * コード使用後の残りコード数テスト
     */
    public function test_remaining_count_decreases_after_use(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);
        $initialCount = $this->service->getRemainingCount($member);

        $this->service->validate($member, $codes[0]);

        $remainingAfterUse = $this->service->getRemainingCount($member);

        $this->assertEquals($initialCount - 1, $remainingAfterUse);
    }

    /**
     * 回復コード存在チェックテスト
     */
    public function test_has_recovery_codes_returns_true_when_codes_exist(): void
    {
        $member = Member::factory()->create();
        $this->service->generate($member);

        $result = $this->service->hasRecoveryCodes($member);

        $this->assertTrue($result);
    }

    /**
     * 回復コードなしのチェックテスト
     */
    public function test_has_recovery_codes_returns_false_when_no_codes(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->hasRecoveryCodes($member);

        $this->assertFalse($result);
    }

    /**
     * コードフォーマットテスト
     */
    public function test_format_code_adds_hyphens(): void
    {
        $code = '12345678901234567890';

        $formatted = $this->service->formatCode($code);

        $this->assertEquals('12345-67890-12345-67890', $formatted);
    }

    /**
     * 全コード無効化テスト
     */
    public function test_revoke_all_disables_all_codes(): void
    {
        $member = Member::factory()->create();
        $codes = $this->service->generate($member);

        $revokedCount = $this->service->revokeAll($member);

        $this->assertEquals(count($codes), $revokedCount);
        $this->assertEquals(0, $this->service->getRemainingCount($member));
    }

    /**
     * 再生成可能チェック - 初回
     */
    public function test_can_regenerate_returns_true_for_first_time(): void
    {
        $member = Member::factory()->create();

        $result = $this->service->canRegenerate($member);

        $this->assertTrue($result);
    }

    /**
     * 再生成可能チェック - 直後は不可
     */
    public function test_can_regenerate_returns_false_immediately_after_generation(): void
    {
        $member = Member::factory()->create();
        $this->service->generate($member);

        $result = $this->service->canRegenerate($member);

        $this->assertFalse($result);
    }

    /**
     * 再生成可能チェック - 時間経過後は可能
     */
    public function test_can_regenerate_returns_true_after_interval(): void
    {
        $member = Member::factory()->create();
        $this->service->generate($member);

        // Default regenerate interval is 90 hours; back-date past that.
        MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->update(['created_at' => Carbon::now()->subHours(91)]);

        $result = $this->service->canRegenerate($member);

        $this->assertTrue($result);
    }

    /**
     * 次回再生成可能日時の取得テスト
     */
    public function test_get_next_regenerate_time(): void
    {
        $member = Member::factory()->create();
        $this->service->generate($member);

        $nextTime = $this->service->getNextRegenerateTime($member);

        $this->assertInstanceOf(Carbon::class, $nextTime);
        $this->assertTrue($nextTime->greaterThan(Carbon::now()));
    }

    /**
     * 未生成時の次回再生成可能日時はnull
     */
    public function test_get_next_regenerate_time_returns_null_when_no_codes(): void
    {
        $member = Member::factory()->create();

        $nextTime = $this->service->getNextRegenerateTime($member);

        $this->assertNull($nextTime);
    }

    /**
     * 他のユーザーのコードに影響しないことのテスト
     */
    public function test_operations_do_not_affect_other_users(): void
    {
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();

        $this->service->generate($member1);
        $this->service->generate($member2);

        $this->service->revokeAll($member1);

        $this->assertEquals(0, $this->service->getRemainingCount($member1));
        $this->assertGreaterThan(0, $this->service->getRemainingCount($member2));
    }
}
