<?php

namespace App\Services;

use App\Helpers\TwoFactorHelper;
use App\Services\EmailAuthenticationService;
use App\Services\DeviceAuthenticationService;
use App\Services\BiometricAuthenticationService;
use App\Enums\TwoFactorMethod;
use Illuminate\Support\Facades\Log;

class AdminTwoFactorService
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
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user);
        
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
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user);
        
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
     * @param mixed $member ユーザーモデル
     * @return bool 2FAが必要かどうか
     */
    public function has($member): bool
    {
        return $this->helper->isTwoFactorEnabled($member);
    }

    /**
     * 異なる環境からのアクセスかどうかを判定
     *
     * @param mixed $member ユーザーモデル
     * @return bool 異なる環境かどうか
     */
    public function isDifferentEnvironment($member): bool
    {
        return $this->helper->isDifferentEnvironment($member);
    }

    /**
     * 二段階認証が必要かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @return bool
     */
    public function isRequired($user): bool
    {
        return $this->helper->isTwoFactorEnabled($user);
    }

    /**
     * システム設定を取得
     *
     * @return array
     */
    public function getSystemSettings(): array
    {
        return $this->helper->getSystemTwoFactorSettings();
    }

    /**
     * 使用する認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        return $this->helper->getEffectiveAuthMethod($user);
    }



    /**
     * 利用可能な認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return array 利用可能な認証方法
     */
    public function getAvailableMethods($user): array
    {
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
     * 信頼済みデバイスとして登録
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
        return $this->emailAuth->generateAndSendCode($user, 'admin');
    }

    /**
     * デバイス認証チャレンジを生成
     *
     * @param mixed $user ユーザーモデル
     * @return \App\Models\MembersTwoFactorDevice チャレンジデータ
     */
    private function generateDeviceChallenge($user): \App\Models\MembersTwoFactorDevice
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
}
