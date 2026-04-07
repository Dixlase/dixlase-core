<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Helpers;

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\TwoFa\TwoFaUtilityTrait;
use Illuminate\Support\Facades\Log;

class TwoFaHelper
{
    use TwoFaUtilityTrait;

    /**
     * 設定値を取得する（SecuritySettingから）
     *
     * @param  string  $key  設定キー
     * @param  mixed  $default  デフォルト値
     * @return mixed 設定値
     */
    protected function getSettingValue(string $key, $default = null)
    {
        return SecuritySetting::getValue($key, $default);
    }

    /**
     * メール設定が完了しているかチェック
     * DB（管理画面）の設定を優先して確認する
     *
     * @return bool メール設定が完了しているか
     */
    public function isMailConfigured(): bool
    {
        $mailer = ConfigHelper::getMailMailer();

        // メール設定が存在しない場合
        if (! $mailer) {
            return false;
        }

        // SMTPの場合、必須設定をチェック
        if ($mailer === 'smtp') {
            $host = ConfigHelper::getMailHost();
            $port = ConfigHelper::getMailPort();

            if (empty($host) || $port <= 0) {
                return false;
            }
        }

        // 送信元アドレスが設定されているかチェック
        $fromAddress = ConfigHelper::getMailFromAddress();
        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            return false;
        }

        return true;
    }

    /**
     * 二段階認証コードを生成してメール送信
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $mailClass  メールクラス名
     * @param  int  $expireMinutes  有効期限（分）
     * @param  string  $context  コンテキスト（admin, user等）
     * @return string 生成されたコード
     *
     * @throws \Exception メール設定が未完了の場合
     */
    public function generateAndSendCode($user, string $mailClass, ?int $expireMinutes = null, string $context = 'admin'): string
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->generateAndSend($user, $mailClass, $expireMinutes, $context);
    }

    /**
     * 二段階認証設定を取得（メンバーまたはユーザー）
     *
     * @param  string|null  $settingModelClass  設定モデルクラス名（null=SecuritySetting）
     */
    public function getTwoFaSettings(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;

        return [
            'two_fa_mode' => (int) $settingModelClass::getValue('two_fa_mode', '0'),
            'enabled_methods' => $this->getEnabledTwoFaMethods($settingModelClass),
            'default_method' => (int) $settingModelClass::getValue('default_two_fa_method', (string) TwoFaMethod::EMAIL->value),
        ];
    }

    /**
     * 有効な二段階認証方法を取得（グローバル設定ベース）
     *
     * @param  string|null  $settingModelClass  設定モデルクラス名（null=SecuritySetting）
     */
    public function getEnabledTwoFaMethods(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;
        $methods = [];

        // メール認証（常に有効）
        $methods[] = TwoFaMethod::EMAIL->value;

        // Passkey認証（two_fa_passkey_modeが0以外なら有効）
        $passkeyMode = (int) $settingModelClass::getValue('two_fa_passkey_mode', '2');
        if ($passkeyMode > 0) {
            $methods[] = TwoFaMethod::PASSKEY->value;
        }

        return $methods;
    }

    /**
     * 特定のメンバーに対して利用可能な二段階認証方法を取得
     * Passkeyデバイスが未登録の場合はPasskeyを除外
     *
     * @param  mixed  $member  メンバーモデル
     * @param  string|null  $settingModelClass  設定モデルクラス名（null=SecuritySetting）
     */
    public function getAvailableTwoFaMethodsForMember($member, ?string $settingModelClass = null): array
    {
        $methods = $this->getEnabledTwoFaMethods($settingModelClass);

        // Passkeyが有効な場合、デバイスが登録されているかチェック
        if (in_array(TwoFaMethod::PASSKEY->value, $methods)) {
            $passkeyService = app(TwoFaPasskeyService::class);
            $devices = $passkeyService->getDevices($member);

            // デバイスが未登録の場合はPasskeyを除外
            if ($devices->isEmpty()) {
                $methods = array_values(array_filter($methods, function ($method) {
                    return $method !== TwoFaMethod::PASSKEY->value;
                }));
            }
        }

        return $methods;
    }

    /**
     * 二段階認証が有効かどうかを判定
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string|null  $settingModelClass  設定モデルクラス名（null=SecuritySetting）
     */
    public function isTwoFaEnabled($user, ?string $settingModelClass = null): bool
    {
        // メール設定が未完了の場合は二段階認証を無効化
        if (! $this->isMailConfigured()) {
            Log::warning('[2FA] メール設定が未完了のため、二段階認証を無効化しています');

            return false;
        }

        $systemSettings = $this->getTwoFaSettings($settingModelClass);
        $twoFaMode = $systemSettings['two_fa_mode'] ?? 0;

        Log::info('[2FA] isTwoFaEnabled check', [
            'user_id' => $user->id,
            'setting_class' => $settingModelClass,
            'two_fa_mode' => $twoFaMode,
            'user_two_fa_mode' => $user->two_fa_mode ?? null,
        ]);

        // 全体設定: 0=Disabled（無効）, 1=DifferentDevice（異なるデバイス）, 2=Always（常に有効）, 3=UseProfileSetting（プロフィール設定に従う）

        // 無効の場合
        if ($twoFaMode === AuthenticationMode::Disabled->value) {
            Log::info('[2FA] Disabled by global setting');

            return false;
        }

        // 全体設定で常に有効の場合
        if ($twoFaMode === AuthenticationMode::Always->value) {
            Log::info('[2FA] Always enabled by global setting');

            return true;
        }

        // 全体設定が「異なるデバイス」の場合
        if ($twoFaMode === AuthenticationMode::DifferentDevice->value) {
            $isDifferent = $this->isDifferentEnvironment($user);
            Log::info('[2FA] DifferentDevice mode', ['is_different' => $isDifferent]);

            return $isDifferent;
        }

        // プロフィール設定を使用する場合（two_fa_mode = UseProfileSetting）
        $userMode = $user->two_fa_mode;

        // AuthenticationMode Enumの場合
        if ($userMode instanceof \App\Enums\AuthenticationMode) {
            $userModeValue = $userMode->value;
        } else {
            // 整数値の場合
            $userModeValue = (int) $userMode;
        }

        Log::info('[2FA] Using user profile setting', ['user_mode_value' => $userModeValue]);

        // ユーザー設定が無効の場合
        if ($userModeValue === AuthenticationMode::Disabled->value) {
            Log::info('[2FA] Disabled by user setting');

            return false;
        }

        // ユーザー設定が常に有効の場合
        if ($userModeValue === AuthenticationMode::Always->value) {
            Log::info('[2FA] Always enabled by user setting');

            return true;
        }

        // ユーザー設定が「異なるデバイス」の場合
        if ($userModeValue === AuthenticationMode::DifferentDevice->value) {
            $isDifferent = $this->isDifferentEnvironment($user);
            Log::info('[2FA] DifferentDevice mode by user setting', ['is_different' => $isDifferent]);

            return $isDifferent;
        }

        Log::info('[2FA] No condition matched, returning false');

        return false;
    }

    /**
     * 使用する認証方法を決定
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string|null  $settingModelClass  設定モデルクラス名（null=SecuritySetting）
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user, ?string $settingModelClass = null): int
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;
        $userMethod = $user->two_fa_default_method ?? null;
        $defaultMethod = (int) $settingModelClass::getValue('default_two_fa_method', (string) TwoFaMethod::EMAIL->value);
        $enabledMethods = $this->getEnabledTwoFaMethods($settingModelClass);
        $globalTwoFaMode = (int) $settingModelClass::getValue('force_two_fa', (string) AuthenticationMode::Disabled->value);

        // ユーザーがパスキーを無効にしている場合は、有効な方法からパスキーを除外
        $userPasskeyEnabled = $user->two_fa_passkey_enabled ?? true;
        $userEnabledMethods = $enabledMethods;
        if (! $userPasskeyEnabled) {
            $userEnabledMethods = array_values(array_filter($enabledMethods, function ($method) {
                return $method !== TwoFaMethod::PASSKEY->value;
            }));
        }

        // Passkeyデバイスが未登録の場合は、有効な方法からPasskeyを除外
        if (in_array(TwoFaMethod::PASSKEY->value, $userEnabledMethods)) {
            $passkeyService = app(TwoFaPasskeyService::class);
            $devices = $passkeyService->getDevices($user);

            if ($devices->isEmpty()) {
                $userEnabledMethods = array_values(array_filter($userEnabledMethods, function ($method) {
                    return $method !== TwoFaMethod::PASSKEY->value;
                }));
            }
        }

        Log::info('[2FA] getEffectiveAuthMethod', [
            'user_id' => $user->id,
            'user_method' => $userMethod,
            'default_method' => $defaultMethod,
            'global_mode' => $globalTwoFaMode,
            'enabled_methods' => $enabledMethods,
            'user_passkey_enabled' => $userPasskeyEnabled,
            'user_enabled_methods' => $userEnabledMethods,
        ]);

        // ユーザーが明示的に認証方法を設定している場合は、それを優先
        if ($userMethod !== null && in_array((int) $userMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using user method', ['method' => (int) $userMethod]);

            return (int) $userMethod;
        }

        // ユーザー設定がない場合は、グローバルのデフォルト認証方法を使用
        if (in_array($defaultMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using global default method', ['method' => $defaultMethod]);

            return $defaultMethod;
        }

        // デフォルト方法が有効でない場合は、有効な方法の最初のものを使用
        $fallbackMethod = ! empty($userEnabledMethods) ? $userEnabledMethods[0] : TwoFaMethod::EMAIL->value;
        Log::info('[2FA] Using fallback method', ['method' => $fallbackMethod]);

        return $fallbackMethod;
    }

    /**
     * 認証方法に応じたメールクラス名を取得
     *
     * @param  int  $method  認証方法
     * @param  string  $context  コンテキスト（admin, user等）
     * @return string メールクラス名
     */
    public function getMailClassForMethod(int $method, string $context = 'admin'): string
    {
        $contextPrefix = ucfirst($context);

        return match ($method) {
            TwoFaMethod::EMAIL->value => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
            TwoFaMethod::DEVICE->value => "App\\Mail\\{$contextPrefix}TwoFactorDeviceVerificationMail",
            TwoFaMethod::BIOMETRIC->value => "App\\Mail\\{$contextPrefix}TwoFactorBiometricMail",
            default => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
        };
    }

    /**
     * 二段階認証の統計情報を取得
     *
     * @return array 統計情報
     */
    public function getTwoFaStats(): array
    {
        // 実装例：実際の統計取得ロジックを追加
        return [
            'total_users_with_two_fa' => 0,
            'active_tokens' => 0,
            'failed_attempts_today' => 0,
        ];
    }

    /**
     * 期限切れトークンをクリーンアップ
     *
     * @return int 削除されたトークン数
     */
    public function cleanupExpiredTokens(): int
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->cleanupExpired();
    }

    /**
     * 認証方法に応じたルート名を取得
     *
     * @param  string  $prefix  ルートプレフィックス（例: 'admin', 'dixlase-users::mypage'）
     * @param  int  $method  認証方法（TwoFaMethod enum値）
     * @return string ルート名（例: 'admin.two-fa.email.show'）
     */
    public static function getTwoFaMethodRoute(string $prefix, int $method): string
    {
        return match ($method) {
            TwoFaMethod::EMAIL->value => "{$prefix}.two-fa.email.show",
            default => "{$prefix}.two-fa.email.show",
        };
    }
}
