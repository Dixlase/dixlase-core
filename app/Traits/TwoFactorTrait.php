<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\MembersTwoFactorToken;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;
use App\Traits\DeviceDetectionTrait;

trait TwoFactorTrait
{
    use DeviceDetectionTrait;
    /**
     * 二段階認証コードを生成してデータベースに保存
     *
     * @param mixed $user ユーザーモデル
     * @param int $expireMinutes 有効期限（分）
     * @return string 生成されたコード
     */
    public function generateTwoFactorCode($user, int $expireMinutes = null): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // デフォルトの有効期限設定（メンバー設定 > コンフィグ）
        if ($expireMinutes === null) {
            $expireMinutes = (int) \App\Models\MemberSetting::getValue('two_factor_expire_minutes', config('two-factor.code_expiration', 5));
        }

        // 古いコードを削除
        MembersTwoFactorToken::where('member_id', $user->id)->delete();

        // 新しいコードを保存
        MembersTwoFactorToken::create([
            'member_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);

        Log::info("[2FA] コード生成: ユーザーID {$user->id}");

        return $code;
    }

    /**
     * 二段階認証コードを検証
     *
     * @param mixed $user ユーザーモデル
     * @param string $inputCode 入力されたコード
     * @return bool 検証結果
     */
    public function validateTwoFactorCode($user, string $inputCode): bool
    {
        $token = MembersTwoFactorToken::where('member_id', $user->id)->latest()->first();

        if (!$token || now()->greaterThan($token->expires_at) || !Hash::check($inputCode, $token->code)) {
            return false;
        }

        // 使い切りコードなので削除
        $token->delete();

        return true;
    }

    /**
     * 有効な認証方法を取得
     *
     * @param string $settingsKey 設定キー
     * @param array $defaultMethods デフォルトの認証方法
     * @return array 有効な認証方法の配列
     */
    public function getEnabledTwoFactorMethods(string $settingsKey = 'enabled_two_factor_methods', array $defaultMethods = null): array
    {
        if ($defaultMethods === null) {
            $defaultMethods = [TwoFactorMethod::EMAIL->value];
        }

        $settingsValue = $this->getSettingValue($settingsKey, json_encode($defaultMethods));
        
        // 設定値が文字列でない場合（整数など）の処理
        if (!is_string($settingsValue)) {
            return $defaultMethods;
        }
        
        $decoded = json_decode($settingsValue, true);
        
        // JSON デコードが失敗した場合、または配列でない場合はデフォルトを返す
        if (!is_array($decoded)) {
            return $defaultMethods;
        }
        
        return $decoded;
    }

    /**
     * 二段階認証が必要かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @param int $forceSetting システム設定の強制2FA設定
     * @param array $enabledMethods 有効な認証方法
     * @return bool 2FAが必要かどうか
     */
    public function requiresTwoFactor($user, int $forceSetting, array $enabledMethods): bool
    {
        // 有効な認証方法がない場合は2FAを無効化
        if (empty($enabledMethods)) {
            return false;
        }

        // 現在の設定に基づいて2FAが必要かチェック
        $modeValue = $this->getEffectiveTwoFactorMode($user, $forceSetting);
        $mode = TwoFactorMode::tryFrom($modeValue);

        // Passkeyが有効な場合は常に2FAを要求
        if (in_array(TwoFactorMethod::PASSKEY->value, $enabledMethods)) {
            return true;
        }

        return match ($mode) {
            TwoFactorMode::Always => true,
            default => false,
        };
    }

    /**
     * 有効な2要素認証モードを取得する
     *
     * @param mixed $user ユーザーモデル
     * @param int $forceSetting システム設定の強制2FA設定
     * @return int 有効な2FAモード
     */
    protected function getEffectiveTwoFactorMode($user, int $forceSetting): int
    {
        // システム設定で2FAが無効化されている場合
        if ($forceSetting === TwoFactorMode::Disabled->value) {
            return TwoFactorMode::Disabled->value;
        }

        // システム設定で強制されている場合
        if ($forceSetting === TwoFactorMode::Always->value) {
            return TwoFactorMode::Always->value;
        }

        // プロフィール設定を使用する場合
        if ($forceSetting === TwoFactorMode::UseProfileSetting->value) {
            return $this->checkMemberSetting($user);
        }

        return TwoFactorMode::Disabled->value;
    }

    /**
     * メンバーの個人設定をチェック
     *
     * @param mixed $user ユーザーモデル
     * @return int 2FAモード
     */
    protected function checkMemberSetting($user): int
    {
        $mode = $user->two_factor_mode;

        // null の場合はデフォルトで無効
        if ($mode === null) {
            return TwoFactorMode::Disabled->value;
        }

        return match ($mode) {
            TwoFactorMode::Always => TwoFactorMode::Always->value,
            default => TwoFactorMode::Disabled->value,
        };
    }


    /**
     * 信頼済みデバイスからのアクセスかチェック
     *
     * @param mixed $user ユーザーモデル
     * @return bool 信頼済みデバイスの場合true
     */
    protected function isFromTrustedDevice($user): bool
    {
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        return $passkeyService->isTrustedDevice($user);
    }

    /**
     * 設定値を取得する（継承先で実装）
     *
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed 設定値
     */
    abstract protected function getSettingValue(string $key, $default = null);
}
