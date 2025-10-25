<?php

namespace App\Services;

use App\Helpers\TwoFactorHelper;
use App\Services\EmailAuthenticationService;
use App\Services\DeviceAuthenticationService;
use App\Services\BiometricAuthenticationService;
use App\Enums\TwoFactorMethod;
use Illuminate\Support\Facades\Log;

/**
 * ユーザー管理プラグイン用の二段階認証サービス
 * 
 * このクラスは、ユーザー管理プラグインで二段階認証機能を使用する際の
 * サンプル実装です。プラグイン独自の設定システムを使用する場合は、
 * 必要に応じてカスタマイズしてください。
 */
class UserTwoFactorService
{
    protected TwoFactorHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    protected DeviceAuthenticationService $deviceAuth;
    protected BiometricAuthenticationService $biometricAuth;

    public function __construct(
        TwoFactorHelper $helper,
        EmailAuthenticationService $emailAuth,
        DeviceAuthenticationService $deviceAuth,
        BiometricAuthenticationService $biometricAuth
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->deviceAuth = $deviceAuth;
        $this->biometricAuth = $biometricAuth;
    }

    /**
     * 二段階認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @param int|null $method 認証方法（nullの場合は自動判定）
     * @return string|array 生成されたコードまたはチャレンジデータ
     */
    public function generate($user, int $method = null)
    {
        $effectiveMethod = $method ?? $this->getEffectiveAuthMethod($user);
        
        return match ($effectiveMethod) {
            TwoFactorMethod::EMAIL->value => $this->generateEmailCode($user),
            TwoFactorMethod::DEVICE->value => $this->generateDeviceChallenge($user),
            TwoFactorMethod::BIOMETRIC->value => $this->generateBiometricChallenge($user),
            default => $this->generateEmailCode($user),
        };
    }

    /**
     * 二段階認証を検証
     *
     * @param mixed $user ユーザーモデル
     * @param string|array $input 入力されたコードまたは認証データ
     * @param int|null $method 認証方法（nullの場合は自動判定）
     * @return bool 検証結果
     */
    public function validate($user, $input, int $method = null): bool
    {
        $effectiveMethod = $method ?? $this->getEffectiveAuthMethod($user);
        
        return match ($effectiveMethod) {
            TwoFactorMethod::EMAIL->value => $this->validateEmailCode($user, $input),
            TwoFactorMethod::DEVICE->value => $this->validateDeviceAuth($user, $input),
            TwoFactorMethod::BIOMETRIC->value => $this->validateBiometricAuth($user, $input),
            default => $this->validateEmailCode($user, $input),
        };
    }

    /**
     * 二段階認証が必要かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @return bool 2FAが必要かどうか
     */
    public function has($user): bool
    {
        // プラグイン独自の設定システムを使用する場合は、
        // ここでプラグインの設定を取得するロジックを実装
        return $this->helper->isTwoFactorEnabled($user);
    }

    /**
     * 使用する認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        // プラグイン独自の設定がある場合は、ここでカスタマイズ
        return $this->helper->getEffectiveAuthMethod($user);
    }

    /**
     * デバイスを信頼済みとして登録
     *
     * @param mixed $user ユーザーモデル
     * @param string|null $deviceName デバイス名
     * @return string トークン
     */
    public function registerTrustedDevice($user, string $deviceName = null): string
    {
        return $this->deviceAuth->registerTrustedDevice($user, $deviceName);
    }

    /**
     * 利用可能な認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return array 利用可能な認証方法
     */
    public function getAvailableMethods($user): array
    {
        // プラグイン独自の設定システムを使用する場合は、
        // ここでプラグインの設定を取得するロジックを実装
        $systemSettings = $this->getSystemSettings();
        $methods = [];

        foreach ($systemSettings['enabled_methods'] as $method) {
            $available = match ($method) {
                TwoFactorMethod::EMAIL->value => true,
                TwoFactorMethod::DEVICE->value => true,
                TwoFactorMethod::BIOMETRIC->value => $this->biometricAuth->isAvailable(),
                default => false,
            };

            if ($available) {
                $methods[] = [
                    'value' => $method,
                    'label' => TwoFactorMethod::from($method)->label(),
                    'setup_required' => $this->isSetupRequired($user, $method),
                ];
            }
        }

        return $methods;
    }

    /**
     * システムの二段階認証設定を取得
     * 
     * プラグイン独自の設定システムを使用する場合は、
     * このメソッドをオーバーライドしてください。
     *
     * @return array 設定配列
     */
    protected function getSystemSettings(): array
    {
        // 例：プラグイン独自の設定を取得
        // return [
        //     'force_2fa' => (int) PluginSetting::get('user_force_2fa', 0),
        //     'enabled_methods' => json_decode(PluginSetting::get('user_enabled_2fa_methods', '[0]'), true),
        //     'default_method' => (int) PluginSetting::get('user_default_2fa_method', 0),
        // ];
        
        // デフォルトはシステム設定を使用
        return $this->helper->getSystemTwoFactorSettings();
    }

    /**
     * 認証方法のセットアップが必要かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @param int $method 認証方法
     * @return bool セットアップが必要かどうか
     */
    private function isSetupRequired($user, int $method): bool
    {
        return match ($method) {
            TwoFactorMethod::EMAIL->value => false, // メール認証は常に利用可能
            TwoFactorMethod::DEVICE->value => !$this->deviceAuth->isTrustedDevice($user),
            TwoFactorMethod::BIOMETRIC->value => !$this->biometricAuth->hasCredentials($user),
            default => true,
        };
    }

    /**
     * メール認証コードを生成
     *
     * @param mixed $user ユーザーモデル
     * @return string 生成されたコード
     */
    private function generateEmailCode($user): string
    {
        return $this->emailAuth->generateAndSendCode($user, 'user');
    }

    /**
     * デバイス認証チャレンジを生成
     *
     * @param mixed $user ユーザーモデル
     * @return array チャレンジデータ
     */
    private function generateDeviceChallenge($user): array
    {
        return $this->deviceAuth->generateDeviceChallenge($user);
    }

    /**
     * 生体認証チャレンジを生成
     *
     * @param mixed $user ユーザーモデル
     * @return array チャレンジデータ
     */
    private function generateBiometricChallenge($user): array
    {
        return $this->biometricAuth->generateAuthenticationChallenge($user);
    }

    /**
     * メール認証コードを検証
     *
     * @param mixed $user ユーザーモデル
     * @param string $inputCode 入力されたコード
     * @return bool 検証結果
     */
    private function validateEmailCode($user, string $inputCode): bool
    {
        return $this->emailAuth->validateCode($user, $inputCode);
    }

    /**
     * デバイス認証を検証
     *
     * @param mixed $user ユーザーモデル
     * @param array $response 認証レスポンス
     * @return bool 検証結果
     */
    private function validateDeviceAuth($user, array $response): bool
    {
        return $this->deviceAuth->verifyDeviceChallenge($response);
    }

    /**
     * 生体認証を検証
     *
     * @param mixed $user ユーザーモデル
     * @param array $assertionData 認証データ
     * @return bool 検証結果
     */
    private function validateBiometricAuth($user, array $assertionData): bool
    {
        return $this->biometricAuth->verifyAssertion($user, $assertionData);
    }

    /**
     * 異なる環境からのアクセスかどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @return bool 異なる環境かどうか
     */
    public function isDifferentEnvironment($user): bool
    {
        return $this->helper->isDifferentEnvironment($user);
    }

    /**
     * 信頼済みデバイスからのアクセスかどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @return bool 信頼済みデバイスかどうか
     */
    public function isFromTrustedDevice($user): bool
    {
        return $this->deviceAuth->isTrustedDevice($user);
    }
}
