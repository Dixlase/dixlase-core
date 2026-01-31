<?php

namespace App\Services;

use App\Helpers\LoginLockoutHelper;
use App\Models\SecuritySetting;
use Illuminate\Http\Request;

class AdminLoginLockoutService
{

    /**
     * ログイン試行制限が有効かどうかを確認
     *
     * @return bool
     */
    public function isLockoutEnabled(): bool
    {
        return LoginLockoutHelper::isLockoutEnabled();
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @return bool
     */
    public function isNotificationEnabled(): bool
    {
        return LoginLockoutHelper::isNotificationEnabled();
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     *
     * @param string $identifier
     * @return bool
     */
    public function isLockedOut(string $identifier): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (!$settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $settings['time_window']
        );

        return $failedAttempts >= $settings['max_attempts'];
    }

    /**
     * IPアドレスがロックアウトされているかを確認
     *
     * @param string $ipAddress
     * @return bool
     */
    public function isIpLockedOut(string $ipAddress): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (!$settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $settings['time_window']
        );

        // IP用の最大試行回数を取得（セキュリティ設定から）
        $maxAttemptsForIp = SecuritySetting::get('login_attempt_max_attempts_ip', $settings['max_attempts'] * 2);
        return $failedAttempts >= $maxAttemptsForIp;
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     *
     * @param string $identifier
     * @return int|null
     */
    public function getLockoutRemainingMinutes(string $identifier): ?int
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        return LoginLockoutHelper::getLockoutRemainingMinutes($identifier, $settings['lockout_duration']);
    }

    /**
     * 成功したログイン後の処理
     *
     * @param string $identifier
     * @return void
     */
    public function handleSuccessfulLogin(string $identifier): void
    {
        LoginLockoutHelper::clearFailedAttempts($identifier);
    }

    /**
     * 失敗したログイン後の処理
     *
     * @param Request $request
     * @param string $identifier
     * @return array
     */
    public function handleFailedLogin(Request $request, string $identifier): array
    {
        return LoginLockoutHelper::recordAndCheckLockout($request, $identifier, false);
    }

    /**
     * ロックアウト状態の詳細情報を取得
     *
     * @param string $identifier
     * @param string $ipAddress
     * @return array
     */
    public function getLockoutStatusDetails(string $identifier, string $ipAddress): array
    {
        return LoginLockoutHelper::getLockoutStatusDetails($identifier, $ipAddress);
    }

    /**
     * ロックアウトエラーメッセージを生成
     *
     * @param array $lockoutInfo
     * @return string
     */
    public function generateLockoutMessage(array $lockoutInfo): string
    {
        return LoginLockoutHelper::generateLockoutMessage($lockoutInfo, 'admin');
    }
}
