<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン識別子確認ヘルパー
 *
 * メールアドレスまたはアカウント名の存在確認を行う共通機能
 * 管理者ログインとユーザーログインの両方で使用可能
 */
class IdentifierCheckHelper
{
    /**
     * 識別子確認を実行（レート制限付き）
     *
     * @param  string  $login  ログイン識別子（メールアドレスまたはアカウント名）
     * @param  string  $ipAddress  IPアドレス
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @param  array  $settings  ロックアウト設定
     * @param  string  $context  コンテキスト（'admin' または 'user'）
     * @return array ['exists' => bool, 'has_passkey' => bool, 'user' => Model|null]
     *
     * @throws ValidationException
     */
    public static function checkWithRateLimit(
        string $login,
        string $ipAddress,
        string $userModelClass,
        array $settings,
        string $context = 'admin'
    ): array {
        // レート制限が無効の場合はスキップ
        if (! ($settings['enabled'] ?? false)) {
            return self::performCheck($login, $ipAddress, $userModelClass, $context);
        }

        return self::performCheck($login, $ipAddress, $userModelClass, $context);
    }

    /**
     * 識別子確認の実行
     *
     * @param  string  $login  ログイン識別子
     * @param  string  $ipAddress  IPアドレス
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @param  string  $context  コンテキスト
     *
     * @throws ValidationException
     */
    protected static function performCheck(
        string $login,
        string $ipAddress,
        string $userModelClass,
        string $context
    ): array {
        // タイミング攻撃対策：常に一定時間待機（100-300ms）
        $delayMs = random_int(100, 300);
        usleep($delayMs * 1000);

        // ユーザー検索（メールアドレスまたはアカウント名）
        $user = $userModelClass::where('email', $login)
            ->orWhere('account_name', $login)
            ->first();

        if ($user) {
            // ユーザー存在確認成功
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_IDENTIFIER_CHECK, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $user,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::info('Login identifier check successful', [
                'user_id' => $user->id,
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // パスキー登録状況を確認
            $hasPasskey = self::hasPasskey($user);

            return [
                'exists' => true,
                'has_passkey' => $hasPasskey,
                'user' => $user,
            ];
        } else {
            // ユーザー存在確認失敗（セキュリティログ）
            AuditLog::logSecurity(AuditLog::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, [
                'severity' => AuditLog::SEVERITY_WARNING,
                'outcome' => AuditLog::OUTCOME_FAILURE,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::warning('Login identifier not found', [
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // ログイン試行を記録（ロックアウト対策）
            \App\Models\MemberLoginAttempt::recordAttempt(
                $login,
                $ipAddress,
                request()->userAgent() ?? 'Unknown',
                false
            );

            // ロックアウト状態をチェック
            $lockoutInfo = \App\Helpers\LoginLockoutHelper::checkLockoutStatus(
                request(),
                $login,
                self::getLockoutSettings(\App\Models\SecuritySetting::class),
                \App\Models\SecuritySetting::class
            );

            // エラーメッセージを決定
            $errorMessage = __('auth.failed');

            // IPベースのロックアウトをチェック
            if ($lockoutInfo['is_ip_locked_out']) {
                $lockoutDuration = $lockoutInfo['settings']['lockout_duration'] ?? 30;
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutDuration]);
            } elseif ($lockoutInfo['is_locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            // エラーメッセージ情報を含めてValidationExceptionを投げる
            $exception = ValidationException::withMessages([
                'login' => $errorMessage,
            ]);

            // エラーメッセージをカスタムデータとして保存
            $exception->errorBag = 'default';
            $exception->redirectTo = null;

            throw $exception;
        }
    }

    /**
     * パスキーが登録されているか確認
     *
     * @param  mixed  $user  ユーザーモデル
     */
    protected static function hasPasskey($user): bool
    {
        // webauthnCredentials リレーションが存在するか確認
        if (method_exists($user, 'webauthnCredentials')) {
            return $user->webauthnCredentials()->count() > 0;
        }

        // twoFaPasskeys リレーションが存在するか確認（管理者用）
        if (method_exists($user, 'twoFaPasskeys')) {
            return $user->twoFaPasskeys()->count() > 0;
        }

        // passkeys リレーションが存在するか確認（汎用）
        if (method_exists($user, 'passkeys')) {
            return $user->passkeys()->count() > 0;
        }

        return false;
    }

    /**
     * ロックアウト設定を取得
     *
     * @param  string  $settingModelClass  設定モデルクラス名
     */
    public static function getLockoutSettings(string $settingModelClass): array
    {
        return [
            'enabled' => (bool) $settingModelClass::getValue('login_attempt_limit_enabled', false),
            'max_attempts' => (int) $settingModelClass::getValue('login_attempt_max_attempts', 5),
            'max_attempts_ip' => (int) $settingModelClass::getValue('login_attempt_max_attempts_ip', null),
            'time_window' => (int) $settingModelClass::getValue('login_attempt_time_window', 15),
            'lockout_duration' => (int) $settingModelClass::getValue('login_attempt_lockout_duration', 30),
        ];
    }
}
