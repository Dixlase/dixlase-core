<?php

namespace App\Services\TwoFa;

use App\Helpers\TwoFaHelper;
use App\Services\EmailAuthenticationService;
use App\Services\TwoFa\TwoFaAttemptService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Enums\TwoFaMethod;
use Illuminate\Support\Facades\Log;

/**
 * 二段階認証サービス
 * 
 * 管理画面とユーザープラグインで共通の二段階認証機能を提供
 * コアの汎用サービス（TwoFaHelper、EmailAuthenticationServiceなど）を組み合わせて使用
 * 設定モデルクラスとコンテキストをコンストラクタで指定可能
 */
class TwoFaService
{
    protected TwoFaHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    protected TwoFaPasskeyService $passkeyAuth;
    protected TwoFaRecoveryCodeService $recoveryCode;
    protected TwoFaAttemptService $attemptService;
    protected string $settingModelClass;
    protected string $context;

    public function __construct(
        TwoFaHelper $helper,
        EmailAuthenticationService $emailAuth,
        TwoFaPasskeyService $passkeyAuth,
        TwoFaRecoveryCodeService $recoveryCode,
        TwoFaAttemptService $attemptService,
        string $settingModelClass,
        string $context
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->passkeyAuth = $passkeyAuth;
        $this->recoveryCode = $recoveryCode;
        $this->attemptService = $attemptService;
        $this->settingModelClass = $settingModelClass;
        $this->context = $context;
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
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);
        
        return match ($effectiveMethod) {
            TwoFaMethod::EMAIL->value => $this->generateEmailCode($user),
            TwoFaMethod::PASSKEY->value => $this->generatePasskeyChallenge($user),
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
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);
        
        return match ($effectiveMethod) {
            TwoFaMethod::EMAIL->value => $this->validateEmailCode($user, $input),
            TwoFaMethod::PASSKEY->value => $this->validatePasskeyAuth($user, $input),
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
        return $this->helper->isTwoFaEnabled($member, $this->settingModelClass);
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
     * システム設定を取得
     *
     * @return array
     */
    public function getSystemSettings(): array
    {
        return $this->helper->getTwoFaSettings($this->settingModelClass);
    }

    /**
     * 使用する認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        return $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);
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
        
        // パスワードログイン後はパスキーを除外
        $authMethod = session('login.auth_method');
        $isPasswordLogin = $authMethod === 'password';

        foreach ($systemSettings['enabled_methods'] as $method) {
            // パスワードログイン後はパスキーを使用不可
            if ($isPasswordLogin && $method === TwoFaMethod::PASSKEY->value) {
                continue;
            }
            
            $available = match ($method) {
                TwoFaMethod::EMAIL->value => true,
                TwoFaMethod::PASSKEY->value => $this->passkeyAuth->isAvailable(),
                default => false,
            };

            if ($available) {
                $methods[] = [
                    'value' => $method,
                    'label' => TwoFaMethod::from($method)->label(),
                    'setup_required' => $this->isSetupRequired($user, $method),
                ];
            }
        }

        return $methods;
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
            TwoFaMethod::EMAIL->value => false, // メール認証は常に利用可能
            TwoFaMethod::PASSKEY->value => !$this->passkeyAuth->hasCredentials($user),
            default => true,
        };
    }

    /**
     * メール認証コードを生成
     */
    private function generateEmailCode($user): string
    {
        return $this->emailAuth->generateAndSendCode($user, $this->context);
    }

    /**
     * Passkeyチャレンジを生成
     */
    private function generatePasskeyChallenge($user): array
    {
        return $this->passkeyAuth->generatePasskeyChallenge($user);
    }

    /**
     * メール認証コードを検証
     */
    private function validateEmailCode($user, string $inputCode): bool
    {
        // ロックアウトチェック
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Email code validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);
            return false;
        }

        $result = $this->emailAuth->validateCode($user, $inputCode);
        
        // 試行を記録
        $this->attemptService->recordAttempt($user, 'email', $result);
        
        return $result;
    }

    /**
     * Passkey認証を検証
     */
    private function validatePasskeyAuth($user, $input): bool
    {
        // ロックアウトチェック
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Passkey validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);
            return false;
        }

        $result = $this->passkeyAuth->validatePasskeyAuth($user, $input);
        
        // 試行を記録
        $this->attemptService->recordAttempt($user, 'passkey', $result);
        
        return $result;
    }

    /**
     * 回復コードを検証
     */
    public function validateRecoveryCode($user, string $code): bool
    {
        // ロックアウトチェック
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Recovery code validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);
            return false;
        }

        $result = $this->recoveryCode->validate($user, $code);
        
        // 試行を記録
        $this->attemptService->recordAttempt($user, 'recovery_code', $result);
        
        if ($result) {
            // 残数を取得
            $remaining = $this->recoveryCode->getRemainingCount($user);
            
            Log::info('[2FA] Recovery code used', [
                'user_id' => $user->id,
                'context' => $this->context,
                'remaining_codes' => $remaining,
            ]);
        }
        
        return $result;
    }

    /**
     * ロックアウト状態をチェック
     */
    public function checkLockout($user): array
    {
        $isLockedOut = $this->attemptService->isLockedOut($user);
        
        if ($isLockedOut) {
            $remainingTime = $this->attemptService->getRemainingLockoutTime($user);
            
            return [
                'locked_out' => true,
                'remaining_minutes' => $remainingTime,
            ];
        }

        // 試行回数制限チェック
        if ($this->attemptService->hasReachedMaxAttempts($user)) {
            return [
                'locked_out' => true,
                'remaining_minutes' => $this->attemptService->getRemainingLockoutTime($user),
            ];
        }

        return [
            'locked_out' => false,
            'remaining_attempts' => $this->attemptService->getRemainingAttempts($user),
        ];
    }
}
