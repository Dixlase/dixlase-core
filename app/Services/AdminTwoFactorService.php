<?php


namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\MembersTwoFactorLoginCodeMail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\MembersTwoFactorToken;
use Illuminate\Support\Facades\Hash;
use App\Models\MemberSetting;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;

class AdminTwoFactorService
{
    public function generate($user): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        MembersTwoFactorToken::where('member_id', $user->id)->delete(); // 古いコードを削除

        MembersTwoFactorToken::create([
            'member_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('app.two_factor.email_code_expire')),
        ]);

        Mail::to($user->email)->send(new MembersTwoFactorLoginCodeMail($code));

        Log::info("[2FA] コード送信: {$code} to {$user->email}");

        return $code;
    }

    public function validate($user, string $inputCode): bool
    {
        $token = MembersTwoFactorToken::where('member_id', $user->id)->latest()->first();

        if (!$token || now()->greaterThan($token->expires_at) || !Hash::check($inputCode, $token->code)) {
            return false;
        }

        $token->delete(); // 使い切りコード

        return true;
    }

    public function has($member)
    {
        $force = (int) MemberSetting::getValue('force_2fa', 0);
        $enabledMethods = json_decode(
            MemberSetting::getValue('enabled_two_factor_methods', json_encode([TwoFactorMethod::EMAIL->value])),
            true
        );

        // 有効な認証方法がない場合は2FAを無効化
        if (empty($enabledMethods)) {
            return false;
        }

        // 現在の設定に基づいて2FAが必要かチェック
        $modeValue = $this->getEffectiveTwoFactorMode($member, $force);
        $mode = TwoFactorMode::tryFrom($modeValue);

        // デバイス認証が有効で、信頼済みデバイスからのアクセスの場合は2FAをスキップ
        if (in_array(TwoFactorMethod::DEVICE->value, $enabledMethods) && $this->isFromTrustedDevice($member)) {
            return false;
        }

        // 生体認証が有効な場合は常に2FAを要求
        if (in_array(TwoFactorMethod::BIOMETRIC->value, $enabledMethods)) {
            return true;
        }

        // 既存のロジック
        return match ($mode) {
            TwoFactorMode::Always => true,
            TwoFactorMode::OnlyNewDevice => $this->isDifferentEnvironment($member),
            default => false,
        };
    }

    private function checkMemberSetting($member): bool
    {
        $mode = $member->two_factor_mode;

        // null の場合はデフォルトで無効
        if ($mode === null) {
            return false;
        }

        return match ($mode) {
            TwoFactorMode::Always => true,
            TwoFactorMode::OnlyNewDevice => $this->isDifferentEnvironment($member),
            default => false,
        };
    }


    public function isDifferentEnvironment($member): bool
    {

        // 信頼済みデバイスチェックの実装
        // 例: クッキーやセッション、データベースを確認
        // ここでは簡易的な実装
        $trustedDeviceToken = request()->cookie('trusted_device');
        return $trustedDeviceToken && $member->trustedDevices()
            ->where('token', hash('sha256', $trustedDeviceToken))
            ->exists();
    }

    /**
     * 有効な2要素認証モードを取得する
     *
     * @param \App\Models\Member $member
     * @param int $forceSetting システム設定の強制2FA設定 (0: オフ, 1: 管理者のみ, 2: 全ユーザー)
     * @return string 有効な2FAモード (TwoFactorMode の値)
     */
    protected function getEffectiveTwoFactorMode($member, int $forceSetting): int
    {
        // システム設定で2FAが無効化されている場合
        if ($forceSetting === 0) {
            return TwoFactorMode::Disabled->value;
        }
        
        // システム設定で全ユーザーに2FAを強制
        if ($forceSetting === 2) {
            return TwoFactorMode::Always->value;
        }
        
        // システム設定で管理者のみ2FAを強制
        if ($forceSetting === 1 && $member->role->value === MemberRole::SUPER_ADMIN->value) {
            return TwoFactorMode::Always->value;
        }
        
        // ユーザー個別の設定を確認（Eloquent cast により常に TwoFactorMode オブジェクトまたは null）
        $userSetting = $member->two_factor_mode;
        
        // ユーザー設定が有効な値の場合はその値を返す
        if ($userSetting instanceof TwoFactorMode) {
            return $userSetting->value;
        }
        
        // デフォルトは無効
        return TwoFactorMode::Disabled->value;
    }
}
