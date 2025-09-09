<?php

namespace App\Services;

use App\Helpers\LoginLockoutHelper;
use App\Models\MemberSetting;
use App\Traits\LoginLockoutTrait;
use Illuminate\Http\Request;

class AdminLoginLockoutService
{
    use LoginLockoutTrait;

    /**
     * 設定値を取得（LoginLockoutTraitで必要）
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting(string $key, $default = null)
    {
        return MemberSetting::getValue($key, $default);
    }

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
     * 最大試行回数を取得
     *
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->getIntegerSetting('login_attempt_max_attempts', 5);
    }

    /**
     * 時間窓（分）を取得
     *
     * @return int
     */
    public function getTimeWindow(): int
    {
        return $this->getIntegerSetting('login_attempt_time_window', 15);
    }

    /**
     * ロックアウト時間（分）を取得
     *
     * @return int
     */
    public function getLockoutDuration(): int
    {
        return $this->getIntegerSetting('login_attempt_lockout_duration', 30);
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     *
     * @param string $identifier
     * @return bool
     */
    public function isLockedOut(string $identifier): bool
    {
        // LoginLockoutTraitのメソッドを直接使用（デフォルト設定キーで）
        if (!$this->isLockoutEnabled()) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCount(
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

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCountByIp(
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

        $lastFailedAttempt = \App\Models\MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (!$lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->addMinutes($this->getLockoutDuration());
        $now = \Carbon\Carbon::now();

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
     * @return \App\Models\MemberLoginAttempt
     */
    public function recordLoginAttempt(Request $request, string $identifier, bool $successful = false)
    {
        return \App\Models\MemberLoginAttempt::recordAttempt(
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
        \App\Models\MemberLoginAttempt::recordAttempt(
            $identifier,
            request()->ip(),
            request()->userAgent(),
            true
        );

        // 失敗した試行記録をクリア
        \App\Models\MemberLoginAttempt::clearFailedAttempts($identifier);
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
