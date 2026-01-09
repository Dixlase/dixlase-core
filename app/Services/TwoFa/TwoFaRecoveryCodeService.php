<?php

namespace App\Services\TwoFa;

use App\Models\Member;
use App\Models\MemberTwoFaRecoveryCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwoFaRecoveryCodeService
{
    /**
     * 回復コードを生成
     * 
     * @param Member $member
     * @return array 生成された回復コード（平文）の配列
     */
    public function generate(Member $member): array
    {
        // 生成個数を設定から取得（1-5個、デフォルト5個）
        $count = (int) \App\Models\MemberSetting::getValue('two_fa_recovery_codes_count', 5);
        $count = max(1, min(5, $count)); // 1-5の範囲に制限

        // 既存の回復コードを全て無効化
        MemberTwoFaRecoveryCode::where('member_id', $member->id)->update(['disabled' => true]);

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // 20桁の数字を生成（5桁×4ブロック）
            $code = $this->generateCode();
            $codes[] = $code;

            // ハッシュ化して保存
            MemberTwoFaRecoveryCode::create([
                'member_id' => $member->id,
                'code' => Hash::make($code),
                'disabled' => false,
            ]);
        }

        Log::info('[Recovery Code] Generated new codes', [
            'member_id' => $member->id,
            'count' => $count,
        ]);

        return $codes;
    }

    /**
     * 20桁の回復コードを生成（5桁×4ブロック）
     */
    protected function generateCode(): string
    {
        $blocks = [];
        for ($i = 0; $i < 4; $i++) {
            $blocks[] = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        }
        return implode('', $blocks);
    }

    /**
     * 回復コードを検証
     * 
     * @param Member $member
     * @param string $code
     * @return bool
     */
    public function validate(Member $member, string $code): bool
    {
        // ハイフンやスペースを削除
        $code = preg_replace('/[\s\-]/', '', $code);

        // 有効な回復コードを取得
        $recoveryCodes = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->whereNull('used_at')
            ->get();

        foreach ($recoveryCodes as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->code)) {
                // 使用済みとしてマーク
                $recoveryCode->markAsUsed();

                Log::info('[Recovery Code] Code used successfully', [
                    'member_id' => $member->id,
                    'recovery_code_id' => $recoveryCode->id,
                ]);

                return true;
            }
        }

        Log::warning('[Recovery Code] Invalid code attempt', [
            'member_id' => $member->id,
        ]);

        return false;
    }

    /**
     * 残りの有効な回復コード数を取得
     */
    public function getRemainingCount(Member $member): int
    {
        return MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->whereNull('used_at')
            ->count();
    }

    /**
     * 回復コードを再生成可能かチェック
     * 
     * @param Member $member
     * @return bool
     */
    public function canRegenerate(Member $member): bool
    {
        // 最後に生成した日時を取得
        $lastGenerated = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastGenerated) {
            return true; // 未生成の場合は生成可能
        }

        // 24時間経過しているかチェック
        $interval = (int) \App\Models\MemberSetting::getValue('two_fa_recovery_code_regenerate_interval', 24);
        $canRegenerateAt = $lastGenerated->created_at->addHours($interval);

        return Carbon::now()->greaterThanOrEqualTo($canRegenerateAt);
    }

    /**
     * 次回再生成可能な日時を取得
     */
    public function getNextRegenerateTime(Member $member): ?Carbon
    {
        $lastGenerated = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastGenerated) {
            return null;
        }

        $interval = (int) \App\Models\MemberSetting::getValue('two_fa_recovery_code_regenerate_interval', 24);
        return $lastGenerated->created_at->addHours($interval);
    }

    /**
     * 回復コードが存在するかチェック
     */
    public function hasRecoveryCodes(Member $member): bool
    {
        return MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->exists();
    }

    /**
     * 回復コードをフォーマット（表示用）
     * 例: 12345-67890-12345-67890
     */
    public function formatCode(string $code): string
    {
        // 20桁を5桁ずつに分割
        $blocks = str_split($code, 5);
        return implode('-', $blocks);
    }

    /**
     * 全ての回復コードを削除（無効化）
     * 
     * @param Member $member
     * @return int 削除された回復コード数
     */
    public function revokeAll(Member $member): int
    {
        $count = MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->where('disabled', false)
            ->count();

        MemberTwoFaRecoveryCode::where('member_id', $member->id)
            ->update(['disabled' => true]);

        Log::info('[Recovery Code] All codes revoked by admin', [
            'member_id' => $member->id,
            'count' => $count,
        ]);

        return $count;
    }
}
