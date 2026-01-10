<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Traits\TwoFa\TwoFaUtilityTrait;
use App\Models\MemberSetting;
use App\Enums\TwoFaMethod;
use App\Enums\AuthenticationMode;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaPasskeyService;

class TwoFaHelper
{
    use TwoFaUtilityTrait;

    /**
     * 設定値を取得する（MemberSettingから）
     *
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed 設定値
     */
    protected function getSettingValue(string $key, $default = null)
    {
        return MemberSetting::getValue($key, $default);
    }

    /**
     * メール設定が完了しているかチェック
     *
     * @return bool メール設定が完了しているか
     */
    public function isMailConfigured(): bool
    {
        $mailer = config('mail.default');
        
        // メール設定が存在しない場合
        if (!$mailer) {
            return false;
        }
        
        // SMTPの場合、必須設定をチェック
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $username = config('mail.mailers.smtp.username');
            
            if (empty($host) || empty($port)) {
                return false;
            }
        }
        
        // 送信元アドレスが設定されているかチェック
        $fromAddress = config('mail.from.address');
        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            return false;
        }
        
        return true;
    }

    /**
     * 二段階認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @param string $mailClass メールクラス名
     * @param int $expireMinutes 有効期限（分）
     * @param string $context コンテキスト（admin, user等）
     * @return string 生成されたコード
     * @throws \Exception メール設定が未完了の場合
     */
    public function generateAndSendCode($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string
    {
        // メール設定チェック
        if (!$this->isMailConfigured()) {
            Log::error("[2FA] メール設定が未完了のため、二段階認証コードを送信できません");
            throw new \Exception(__('admin/profile.two_fa.mail_not_configured'));
        }
        
        $code = $this->generateTwoFaCode($user, $expireMinutes);

        // メール送信
        try {
            if ($mailClass === \App\Mail\TwoFaCodeMail::class) {
                // 汎用メールクラスの場合はコンテキストを渡す
                Mail::to($user->email)->send(new $mailClass($code, $context));
            } else {
                // 既存のメールクラスの場合は従来通り
                Mail::to($user->email)->send(new $mailClass($code));
            }
            Log::info("[2FA] コード送信成功: ユーザーID {$user->id}, コンテキスト: {$context}");
        } catch (\Exception $e) {
            Log::error("[2FA] コード送信失敗: ユーザーID {$user->id}, エラー: " . $e->getMessage());
            throw $e;
        }

        return $code;
    }

    /**
     * 二段階認証設定を取得（メンバーまたはユーザー）
     *
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return array
     */
    public function getTwoFaSettings(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\MemberSetting::class;
        
        return [
            'two_fa_mode' => (int) $settingModelClass::getValue('two_fa_mode', '0'),
            'enabled_methods' => $this->getEnabledTwoFaMethods($settingModelClass),
            'default_method' => (int) $settingModelClass::getValue('default_two_fa_method', (string)TwoFaMethod::EMAIL->value),
        ];
    }

    /**
     * 有効な二段階認証方法を取得（グローバル設定ベース）
     *
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return array
     */
    public function getEnabledTwoFaMethods(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\MemberSetting::class;
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
     * @param mixed $member メンバーモデル
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return array
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
                $methods = array_values(array_filter($methods, function($method) {
                    return $method !== TwoFaMethod::PASSKEY->value;
                }));
            }
        }
        
        return $methods;
    }

    /**
     * 二段階認証が有効かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return bool
     */
    public function isTwoFaEnabled($user, ?string $settingModelClass = null): bool
    {
        // メール設定が未完了の場合は二段階認証を無効化
        if (!$this->isMailConfigured()) {
            Log::warning("[2FA] メール設定が未完了のため、二段階認証を無効化しています");
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
            $userModeValue = (int)$userMode;
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
     * @param mixed $user ユーザーモデル
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user, ?string $settingModelClass = null): int
    {
        $settingModelClass = $settingModelClass ?? \App\Models\MemberSetting::class;
        $userMethod = $user->two_fa_default_method ?? null;
        $defaultMethod = (int) $settingModelClass::getValue('default_two_fa_method', (string)TwoFaMethod::EMAIL->value);
        $enabledMethods = $this->getEnabledTwoFaMethods($settingModelClass);
        $globalTwoFaMode = (int) $settingModelClass::getValue('force_two_fa', (string)AuthenticationMode::Disabled->value);
        
        // ユーザーがパスキーを無効にしている場合は、有効な方法からパスキーを除外
        $userPasskeyEnabled = $user->two_fa_passkey_enabled ?? true;
        $userEnabledMethods = $enabledMethods;
        if (!$userPasskeyEnabled) {
            $userEnabledMethods = array_values(array_filter($enabledMethods, function($method) {
                return $method !== TwoFaMethod::PASSKEY->value;
            }));
        }
        
        // Passkeyデバイスが未登録の場合は、有効な方法からPasskeyを除外
        if (in_array(TwoFaMethod::PASSKEY->value, $userEnabledMethods)) {
            $passkeyService = app(TwoFaPasskeyService::class);
            $devices = $passkeyService->getDevices($user);
            
            if ($devices->isEmpty()) {
                $userEnabledMethods = array_values(array_filter($userEnabledMethods, function($method) {
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
        if ($userMethod !== null && in_array((int)$userMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using user method', ['method' => (int)$userMethod]);
            return (int)$userMethod;
        }

        // ユーザー設定がない場合は、グローバルのデフォルト認証方法を使用
        if (in_array($defaultMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using global default method', ['method' => $defaultMethod]);
            return $defaultMethod;
        }

        // デフォルト方法が有効でない場合は、有効な方法の最初のものを使用
        $fallbackMethod = !empty($userEnabledMethods) ? $userEnabledMethods[0] : TwoFaMethod::EMAIL->value;
        Log::info('[2FA] Using fallback method', ['method' => $fallbackMethod]);
        return $fallbackMethod;
    }

    /**
     * 認証方法に応じたメールクラス名を取得
     *
     * @param int $method 認証方法
     * @param string $context コンテキスト（admin, user等）
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
        return \App\Models\MemberTwoFaToken::where('expires_at', '<', now())->delete();
    }

    /**
     * 回復コードを生成（手動生成・自動生成共通）
     *
     * @param mixed $user ユーザーモデル
     * @param bool $isAutoGenerated 自動生成かどうか
     * @return array 生成された回復コード配列
     * @throws \Exception 生成に失敗した場合
     */
    public function generateRecoveryCodes($user, bool $isAutoGenerated = false): array
    {
        $recoveryCodeService = new TwoFaRecoveryCodeService();

        try {
            $codes = $recoveryCodeService->generate($user);
            
            $generationType = $isAutoGenerated ? '自動生成' : '手動生成';
            Log::info("[Recovery Codes] {$generationType}成功: ユーザーID {$user->id}, コード数: " . count($codes));

            return $codes;
        } catch (\Exception $e) {
            $generationType = $isAutoGenerated ? '自動生成' : '手動生成';
            Log::error("[Recovery Codes] {$generationType}エラー: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 回復コードを再生成（再生成制限チェック付き）
     *
     * @param mixed $user ユーザーモデル
     * @return array ['success' => bool, 'codes' => array|null, 'message' => string, 'next_time' => string|null]
     */
    public function regenerateRecoveryCodes($user): array
    {
        $recoveryCodeService = new TwoFaRecoveryCodeService();

        try {
            // 再生成可能かチェック
            if (!$recoveryCodeService->canRegenerate($user)) {
                $nextTime = $recoveryCodeService->getNextRegenerateTime($user);
                return [
                    'success' => false,
                    'codes' => null,
                    'message' => __('admin/profile.recovery_codes_regenerate_too_soon', [
                        'time' => $nextTime->format('Y-m-d H:i')
                    ]),
                    'next_time' => $nextTime->format('Y-m-d H:i'),
                ];
            }

            // 回復コードを再生成
            $codes = $recoveryCodeService->generate($user);
            
            Log::info("[Recovery Codes] 再生成成功: ユーザーID {$user->id}, コード数: " . count($codes));

            return [
                'success' => true,
                'codes' => $codes,
                'message' => __('admin/profile.recovery_codes_regenerated'),
                'next_time' => null,
            ];
        } catch (\Exception $e) {
            Log::error("[Recovery Codes] 再生成エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'codes' => null,
                'message' => __('admin/profile.recovery_codes_generation_error'),
                'next_time' => null,
            ];
        }
    }

    /**
     * 回復コードが未生成かチェック
     *
     * @param mixed $user ユーザーモデル
     * @return bool 未生成の場合true
     */
    public function hasNoRecoveryCodes($user): bool
    {
        $recoveryCodeService = new TwoFaRecoveryCodeService();
        return $recoveryCodeService->getRemainingCount($user) === 0;
    }

    // ========================================
    // Passkey（生体認証）管理
    // ========================================

    /**
     * 生体認証の登録チャレンジを生成
     *
     * @param mixed $user ユーザーモデル
     * @return array ['success' => bool, 'challenge' => array|null, 'message' => string]
     */
    public function generateBiometricChallenge($user): array
    {
        try {
            $passkeyService = new TwoFaPasskeyService();

            // 生体認証が利用可能かチェック
            if (!$passkeyService->isAvailable()) {
                return [
                    'success' => false,
                    'challenge' => null,
                    'message' => __('two_fa.biometric.https_required'),
                ];
            }

            // 登録チャレンジを生成
            $challenge = $passkeyService->generateRegistrationChallenge($user);

            Log::info("[Biometric Registration] チャレンジ生成: ユーザーID {$user->id}");

            return [
                'success' => true,
                'challenge' => $challenge,
                'message' => null,
            ];
        } catch (\Exception $e) {
            Log::error("[Biometric Registration] チャレンジ生成エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'challenge' => null,
                'message' => __('two_fa.biometric.challenge_generation_failed'),
            ];
        }
    }

    /**
     * 生体認証を登録
     *
     * @param mixed $user ユーザーモデル
     * @param array $credential 認証情報
     * @param string|null $deviceName デバイス名
     * @return array ['success' => bool, 'message' => string]
     */
    public function registerBiometric($user, array $credential, ?string $deviceName = null): array
    {
        try {
            $passkeyService = new TwoFaPasskeyService();

            // 認証情報を登録
            $passkeyService->registerCredential($user, $credential, $deviceName);

            Log::info("[Biometric Registration] 登録成功: ユーザーID {$user->id}");

            return [
                'success' => true,
                'message' => __('two_fa.biometric.registered_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error("[Biometric Registration] 登録エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two_fa.biometric.registration_failed'),
            ];
        }
    }

    /**
     * 生体認証を削除
     *
     * @param mixed $user ユーザーモデル
     * @param string $credentialId 認証情報ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function revokeBiometric($user, string $credentialId): array
    {
        try {
            $passkeyService = new TwoFaPasskeyService();

            if ($passkeyService->revokeCredential($user, $credentialId)) {
                Log::info("[Biometric Registration] 削除成功: ユーザーID {$user->id}, 認証情報ID: {$credentialId}");

                return [
                    'success' => true,
                    'message' => __('two_fa.biometric.revoked_successfully'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('two_fa.biometric.not_found'),
                ];
            }
        } catch (\Exception $e) {
            Log::error("[Biometric Registration] 削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two_fa.biometric.revocation_failed'),
            ];
        }
    }

    /**
     * すべての生体認証を削除
     *
     * @param mixed $user ユーザーモデル
     * @return array ['success' => bool, 'count' => int, 'message' => string]
     */
    public function revokeAllBiometric($user): array
    {
        try {
            $count = \App\Models\MembersTwoFaDevice::where('member_id', $user->id)->delete();
            
            Log::info("[Biometric Auth] 一括削除成功: ユーザーID {$user->id}, 削除数: {$count}");

            return [
                'success' => true,
                'count' => $count,
                'message' => __('two_fa.biometric.all_revoked_successfully', ['count' => $count]),
            ];
        } catch (\Exception $e) {
            Log::error("[Biometric Auth] 一括削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'count' => 0,
                'message' => __('two_fa.biometric.revoke_all_failed'),
            ];
        }
    }

    // ========================================
    // 信頼済みデバイス管理
    // ========================================

    /**
     * 信頼済みデバイスを削除
     *
     * @param mixed $user ユーザーモデル
     * @param int $deviceId デバイスID
     * @return array ['success' => bool, 'message' => string]
     */
    public function revokeTrustedDevice($user, int $deviceId): array
    {
        try {
            $passkeyService = new TwoFaPasskeyService();

            if ($passkeyService->revokeDevice($user, $deviceId)) {
                Log::info("[Device Auth] 削除成功: ユーザーID {$user->id}, デバイスID: {$deviceId}");

                return [
                    'success' => true,
                    'message' => __('two_fa.trusted_device.revoked_successfully'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('two_fa.trusted_device.not_found'),
                ];
            }
        } catch (\Exception $e) {
            Log::error("[Device Auth] 削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two_fa.trusted_device.revocation_failed'),
            ];
        }
    }

    /**
     * すべての信頼済みデバイスを削除
     *
     * @param mixed $user ユーザーモデル
     * @return array ['success' => bool, 'count' => int, 'message' => string]
     */
    public function revokeAllTrustedDevices($user): array
    {
        try {
            $count = \App\Models\MembersTrustedDevice::where('member_id', $user->id)->delete();
            
            Log::info("[Device Auth] 一括削除成功: ユーザーID {$user->id}, 削除数: {$count}");

            return [
                'success' => true,
                'count' => $count,
                'message' => __('two_fa.trusted_device.all_revoked_successfully', ['count' => $count]),
            ];
        } catch (\Exception $e) {
            Log::error("[Device Auth] 一括削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'count' => 0,
                'message' => __('two_fa.trusted_device.revoke_all_failed'),
            ];
        }
    }
}
