<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Traits\TwoFactorTrait;
use App\Models\MemberSetting;
use App\Enums\TwoFactorMethod;
use App\Enums\AuthenticationMode;

class TwoFactorHelper
{
    use TwoFactorTrait;

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
            throw new \Exception(__('admin/profile.two_factor.mail_not_configured'));
        }
        
        $code = $this->generateTwoFactorCode($user, $expireMinutes);

        // メール送信
        try {
            if ($mailClass === \App\Mail\TwoFactorCodeMail::class) {
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
    public function getTwoFactorSettings(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\MemberSetting::class;
        
        return [
            'force_2fa' => (int) $settingModelClass::getValue('force_2fa', '0'),
            'enabled_methods' => $this->getEnabledTwoFactorMethods($settingModelClass),
            'default_method' => (int) $settingModelClass::getValue('default_two_factor_method', (string)TwoFactorMethod::EMAIL->value),
        ];
    }

    /**
     * 有効な二段階認証方法を取得
     *
     * @param string|null $settingModelClass 設定モデルクラス名（null=MemberSetting）
     * @return array
     */
    public function getEnabledTwoFactorMethods(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\MemberSetting::class;
        $methods = [];
        
        // メール認証
        if ($settingModelClass::getValue('enabled_2fa_email', '1') === '1') {
            $methods[] = TwoFactorMethod::EMAIL->value;
        }
        
        // Passkey認証
        if ($settingModelClass::getValue('enabled_2fa_passkey', '0') === '1') {
            $methods[] = TwoFactorMethod::PASSKEY->value;
        }
        
        // 少なくとも1つの方法は有効にする（デフォルトはメール）
        if (empty($methods)) {
            $methods[] = TwoFactorMethod::EMAIL->value;
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
    public function isTwoFactorEnabled($user, ?string $settingModelClass = null): bool
    {
        // メール設定が未完了の場合は二段階認証を無効化
        if (!$this->isMailConfigured()) {
            Log::warning("[2FA] メール設定が未完了のため、二段階認証を無効化しています");
            return false;
        }
        
        $systemSettings = $this->getTwoFactorSettings($settingModelClass);
        $force2fa = $systemSettings['force_2fa'] ?? 0;
        
        // 全体設定: 0=Disabled（無効）, 1=DifferentDevice（異なるデバイス）, 2=Always（常に有効）, 3=UseProfileSetting（プロフィール設定に従う）
        
        // 無効の場合
        if ($force2fa === AuthenticationMode::Disabled->value) {
            return false;
        }
        
        // 全体設定で常に有効の場合
        if ($force2fa === AuthenticationMode::Always->value) {
            return true;
        }
        
        // プロフィール設定を使用する場合（force_2fa = UseProfileSetting）
        $userMode = $user->two_factor_mode;
        
        // AuthenticationMode Enumの場合
        if ($userMode instanceof \App\Enums\AuthenticationMode) {
            return $userMode->value > 0; // Disabled(0)以外は有効
        }
        
        // 整数値の場合
        return (int)$userMode > 0;
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
        $userMethod = $user->default_two_factor_method ?? null;
        $defaultMethod = (int) $settingModelClass::getValue('default_two_factor_method', (string)TwoFactorMethod::EMAIL->value);
        $enabledMethods = $this->getEnabledTwoFactorMethods($settingModelClass);
        $globalTwoFactorMode = (int) $settingModelClass::getValue('force_2fa', (string)AuthenticationMode::Disabled->value);
        
        // ユーザーがパスキーを無効にしている場合は、有効な方法からパスキーを除外
        $userPasskeyEnabled = $user->two_factor_passkey_enabled ?? true;
        $userEnabledMethods = $enabledMethods;
        if (!$userPasskeyEnabled) {
            $userEnabledMethods = array_values(array_filter($enabledMethods, function($method) {
                return $method !== TwoFactorMethod::PASSKEY->value;
            }));
        }

        Log::info('[2FA] getEffectiveAuthMethod', [
            'user_id' => $user->id,
            'user_method' => $userMethod,
            'default_method' => $defaultMethod,
            'global_mode' => $globalTwoFactorMode,
            'enabled_methods' => $enabledMethods,
            'user_passkey_enabled' => $userPasskeyEnabled,
            'user_enabled_methods' => $userEnabledMethods,
        ]);

        // グローバル設定が「プロフィール設定に従う」(3)以外の場合は、デフォルト認証方法を強制
        if ($globalTwoFactorMode !== AuthenticationMode::UseProfileSetting->value) {
            if (in_array($defaultMethod, $userEnabledMethods, true)) {
                Log::info('[2FA] Using global default method (forced)', ['method' => $defaultMethod, 'global_mode' => $globalTwoFactorMode]);
                return $defaultMethod;
            }
        }

        // グローバル設定が「プロフィール設定に従う」(3)の場合のみ、ユーザー設定を考慮
        if ($userMethod !== null && in_array((int)$userMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using user method', ['method' => (int)$userMethod]);
            return (int)$userMethod;
        }

        // デフォルト方法が有効な場合はそれを使用
        if (in_array($defaultMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using default method', ['method' => $defaultMethod]);
            return $defaultMethod;
        }

        // 有効な方法の最初のものを使用
        $fallbackMethod = !empty($userEnabledMethods) ? $userEnabledMethods[0] : TwoFactorMethod::EMAIL->value;
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
            TwoFactorMethod::EMAIL->value => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
            TwoFactorMethod::DEVICE->value => "App\\Mail\\{$contextPrefix}TwoFactorDeviceVerificationMail",
            TwoFactorMethod::BIOMETRIC->value => "App\\Mail\\{$contextPrefix}TwoFactorBiometricMail",
            default => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
        };
    }

    /**
     * 二段階認証の統計情報を取得
     *
     * @return array 統計情報
     */
    public function getTwoFactorStats(): array
    {
        // 実装例：実際の統計取得ロジックを追加
        return [
            'total_users_with_2fa' => 0,
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
        return \App\Models\Member2faToken::where('expires_at', '<', now())->delete();
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
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);

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
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);

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
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
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
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);

            // 生体認証が利用可能かチェック
            if (!$passkeyService->isAvailable()) {
                return [
                    'success' => false,
                    'challenge' => null,
                    'message' => __('two-factor.biometric.https_required'),
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
                'message' => __('two-factor.biometric.challenge_generation_failed'),
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
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);

            // 認証情報を登録
            $passkeyService->registerCredential($user, $credential, $deviceName);

            Log::info("[Biometric Registration] 登録成功: ユーザーID {$user->id}");

            return [
                'success' => true,
                'message' => __('two-factor.biometric.registered_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error("[Biometric Registration] 登録エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two-factor.biometric.registration_failed'),
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
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);

            if ($passkeyService->revokeCredential($user, $credentialId)) {
                Log::info("[Biometric Registration] 削除成功: ユーザーID {$user->id}, 認証情報ID: {$credentialId}");

                return [
                    'success' => true,
                    'message' => __('two-factor.biometric.revoked_successfully'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('two-factor.biometric.not_found'),
                ];
            }
        } catch (\Exception $e) {
            Log::error("[Biometric Registration] 削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two-factor.biometric.revocation_failed'),
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
            $count = \App\Models\MembersTwoFactorDevice::where('member_id', $user->id)->delete();
            
            Log::info("[Biometric Auth] 一括削除成功: ユーザーID {$user->id}, 削除数: {$count}");

            return [
                'success' => true,
                'count' => $count,
                'message' => __('two-factor.biometric.all_revoked_successfully', ['count' => $count]),
            ];
        } catch (\Exception $e) {
            Log::error("[Biometric Auth] 一括削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'count' => 0,
                'message' => __('two-factor.biometric.revoke_all_failed'),
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
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);

            if ($passkeyService->revokeDevice($user, $deviceId)) {
                Log::info("[Device Auth] 削除成功: ユーザーID {$user->id}, デバイスID: {$deviceId}");

                return [
                    'success' => true,
                    'message' => __('two-factor.trusted_device.revoked_successfully'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('two-factor.trusted_device.not_found'),
                ];
            }
        } catch (\Exception $e) {
            Log::error("[Device Auth] 削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => __('two-factor.trusted_device.revocation_failed'),
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
                'message' => __('two-factor.trusted_device.all_revoked_successfully', ['count' => $count]),
            ];
        } catch (\Exception $e) {
            Log::error("[Device Auth] 一括削除エラー: " . $e->getMessage());
            
            return [
                'success' => false,
                'count' => 0,
                'message' => __('two-factor.trusted_device.revoke_all_failed'),
            ];
        }
    }
}
