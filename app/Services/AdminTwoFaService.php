<?php

namespace App\Services;

use App\Helpers\TwoFaHelper;
use App\Services\EmailAuthenticationService;
use App\Services\PasskeyAuthenticationService;
use App\Services\RecoveryCodeService;
use App\Services\TwoFaAttemptService;
use App\Enums\TwoFaMethod;
use Illuminate\Support\Facades\Log;

class AdminTwoFaService
{
    protected TwoFaHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    protected PasskeyAuthenticationService $passkeyAuth;
    protected RecoveryCodeService $recoveryCode;
    protected TwoFaAttemptService $attemptService;

    public function __construct(
        TwoFaHelper $helper,
        EmailAuthenticationService $emailAuth,
        PasskeyAuthenticationService $passkeyAuth,
        RecoveryCodeService $recoveryCode,
        TwoFaAttemptService $attemptService
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->passkeyAuth = $passkeyAuth;
        $this->recoveryCode = $recoveryCode;
        $this->attemptService = $attemptService;
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
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user);
        
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
        return $this->emailAuth->generateAndSendCode($user, 'admin');
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
                'member_id' => $user->id,
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
                'member_id' => $user->id,
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
