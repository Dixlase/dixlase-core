<?php

namespace App\Services;

use App\Models\MemberLoginAttempt;
use App\Models\MemberSetting;
use App\Services\SystemNotificationService;
use Carbon\Carbon;
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
        return (bool) MemberSetting::getValue('login_attempt_limit_enabled', false);
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @return bool
     */
    public function isNotificationEnabled(): bool
    {
        return (bool) MemberSetting::getValue('lockout_notification_enabled', true);
    }

    /**
     * 最大試行回数を取得
     *
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return (int) MemberSetting::getValue('login_attempt_max_attempts', 5);
    }

    /**
     * 時間窓（分）を取得
     *
     * @return int
     */
    public function getTimeWindow(): int
    {
        return (int) MemberSetting::getValue('login_attempt_time_window', 15);
    }

    /**
     * ロックアウト時間（分）を取得
     *
     * @return int
     */
    public function getLockoutDuration(): int
    {
        return (int) MemberSetting::getValue('login_attempt_lockout_duration', 30);
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     *
     * @param string $identifier
     * @return bool
     */
    public function isLockedOut(string $identifier): bool
    {
        if (!$this->isLockoutEnabled()) {
            return false;
        }

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow()
        );

        return $failedAttempts >= $this->getMaxAttempts();
    }

    /**
     * IPアドレスがロックアウトされているかを確認
     *
     * @param string $ipAddress
     * @return bool
     */
    public function isIpLockedOut(string $ipAddress): bool
    {
        if (!$this->isLockoutEnabled()) {
            return false;
        }

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $this->getTimeWindow()
        );

        // IPアドレスベースのロックアウトは、識別子ベースより厳しく設定
        $maxAttemptsForIp = $this->getMaxAttempts() * 2;

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
        if (!$this->isLockedOut($identifier)) {
            return null;
        }

        $lastFailedAttempt = MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (!$lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->addMinutes($this->getLockoutDuration());
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($lockoutUntil)) {
            return 0; // ロックアウト期間終了
        }

        return $now->diffInMinutes($lockoutUntil, false);
    }

    /**
     * ログイン試行を記録
     *
     * @param Request $request
     * @param string $identifier
     * @param bool $successful
     * @return MemberLoginAttempt
     */
    public function recordLoginAttempt(Request $request, string $identifier, bool $successful = false): MemberLoginAttempt
    {
        return MemberLoginAttempt::recordAttempt(
            $identifier,
            $request->ip(),
            $request->userAgent(),
            $successful
        );
    }

    /**
     * 成功したログイン後の処理
     *
     * @param string $identifier
     * @return void
     */
    public function handleSuccessfulLogin(string $identifier): void
    {
        // 成功したログインを記録
        MemberLoginAttempt::recordAttempt(
            $identifier,
            request()->ip(),
            request()->userAgent(),
            true
        );

        // 過去の失敗記録をクリア
        MemberLoginAttempt::clearFailedAttempts($identifier);
    }

    /**
     * 失敗したログイン後の処理
     *
     * @param Request $request
     * @param string $identifier
     * @return array ロックアウト情報
     */
    public function handleFailedLogin(Request $request, string $identifier): array
    {
        // 失敗したログインを記録
        $this->recordLoginAttempt($request, $identifier, false);

        if (!$this->isLockoutEnabled()) {
            return [
                'locked_out' => false,
                'remaining_attempts' => null,
                'lockout_minutes' => null,
            ];
        }

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow()
        );

        $maxAttempts = $this->getMaxAttempts();
        $isLockedOut = $failedAttempts >= $maxAttempts;

        // ロックアウトが発生した場合、通知を送信
        if ($isLockedOut && $this->isNotificationEnabled()) {
            $this->sendLockoutNotification($identifier, $request->ip(), $failedAttempts);
        }

        return [
            'locked_out' => $isLockedOut,
            'remaining_attempts' => $isLockedOut ? 0 : max(0, $maxAttempts - $failedAttempts),
            'lockout_minutes' => $isLockedOut ? $this->getLockoutDuration() : null,
            'failed_attempts' => $failedAttempts,
        ];
    }

    /**
     * ロックアウト通知を送信
     *
     * @param string $identifier
     * @param string $ipAddress
     * @param int $failedAttempts
     * @return void
     */
    private function sendLockoutNotification(string $identifier, string $ipAddress, int $failedAttempts): void
    {
        try {
            $notificationService = app(SystemNotificationService::class);
            
            $errorDetails = [
                'type' => 'Login Lockout',
                'identifier' => $identifier,
                'ip_address' => $ipAddress,
                'failed_attempts' => $failedAttempts,
                'max_attempts' => $this->getMaxAttempts(),
                'time_window' => $this->getTimeWindow() . ' minutes',
                'lockout_duration' => $this->getLockoutDuration() . ' minutes',
                'timestamp' => now()->toDateTimeString(),
            ];

            $notificationService->sendErrorNotification(
                'Admin Login Lockout Triggered',
                'A user has been locked out due to excessive login attempts.',
                $errorDetails
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send lockout notification: ' . $e->getMessage());
        }
    }

    /**
     * ロックアウト状態の詳細情報を取得
     *
     * @param string $identifier
     * @return array
     */
    public function getLockoutInfo(string $identifier): array
    {
        if (!$this->isLockoutEnabled()) {
            return [
                'enabled' => false,
                'locked_out' => false,
            ];
        }

        $isLockedOut = $this->isLockedOut($identifier);
        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow()
        );

        return [
            'enabled' => true,
            'locked_out' => $isLockedOut,
            'failed_attempts' => $failedAttempts,
            'max_attempts' => $this->getMaxAttempts(),
            'remaining_attempts' => max(0, $this->getMaxAttempts() - $failedAttempts),
            'remaining_minutes' => $isLockedOut ? $this->getLockoutRemainingMinutes($identifier) : null,
            'time_window' => $this->getTimeWindow(),
            'lockout_duration' => $this->getLockoutDuration(),
        ];
    }
}
