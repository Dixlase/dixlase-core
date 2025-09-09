<?php

namespace App\Traits;

use App\Models\MemberLoginAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait LoginLockoutTrait
{
    /**
     * ログイン試行制限が有効かどうかを確認
     *
     * @param string $settingKey デフォルト: 'login_attempt_limit_enabled'
     * @return bool
     */
    public function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, false);
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @param string $settingKey デフォルト: 'lockout_notification_enabled'
     * @return bool
     */
    public function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, true);
    }

    /**
     * 最大試行回数を取得
     *
     * @param string $settingKey デフォルト: 'login_attempt_max_attempts'
     * @return int
     */
    public function getMaxAttempts(string $settingKey = 'login_attempt_max_attempts'): int
    {
        return $this->getIntegerSetting($settingKey, 5);
    }

    /**
     * 時間窓（分）を取得
     *
     * @param string $settingKey デフォルト: 'login_attempt_time_window'
     * @return int
     */
    public function getTimeWindow(string $settingKey = 'login_attempt_time_window'): int
    {
        return $this->getIntegerSetting($settingKey, 15);
    }

    /**
     * ロックアウト時間（分）を取得
     *
     * @param string $settingKey デフォルト: 'login_attempt_lockout_duration'
     * @return int
     */
    public function getLockoutDuration(string $settingKey = 'login_attempt_lockout_duration'): int
    {
        return $this->getIntegerSetting($settingKey, 30);
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     *
     * @param string $identifier
     * @param array $settings 設定配列（オプション）
     * @return bool
     */
    public function isLockedOut(string $identifier, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (!$this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow($timeWindowKey)
        );

        return $failedAttempts >= $this->getMaxAttempts($maxAttemptsKey);
    }

    /**
     * IPアドレスがロックアウトされているかを確認
     *
     * @param string $ipAddress
     * @param array $settings 設定配列（オプション）
     * @return bool
     */
    public function isIpLockedOut(string $ipAddress, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (!$this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $this->getTimeWindow($timeWindowKey)
        );

        // IPアドレスベースのロックアウトは、識別子ベースより厳しく設定
        $maxAttemptsForIp = $this->getMaxAttempts($maxAttemptsKey) * 2;

        return $failedAttempts >= $maxAttemptsForIp;
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     *
     * @param string $identifier
     * @param array $settings 設定配列（オプション）
     * @return int|null
     */
    public function getLockoutRemainingMinutes(string $identifier, array $settings = []): ?int
    {
        $lockoutDurationKey = $settings['lockout_duration_key'] ?? 'login_attempt_lockout_duration';

        if (!$this->isLockedOut($identifier, $settings)) {
            return null;
        }

        $lastFailedAttempt = MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (!$lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->addMinutes($this->getLockoutDuration($lockoutDurationKey));
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

        // 失敗した試行記録をクリア
        MemberLoginAttempt::clearFailedAttempts($identifier);
    }

    /**
     * 失敗したログイン後の処理
     *
     * @param Request $request
     * @param string $identifier
     * @param array $settings 設定配列（オプション）
     * @return array ロックアウト情報
     */
    public function handleFailedLogin(Request $request, string $identifier, array $settings = []): array
    {
        // 失敗した試行を記録
        $this->recordLoginAttempt($request, $identifier, false);

        $lockoutInfo = [
            'is_locked_out' => false,
            'remaining_attempts' => null,
            'lockout_minutes' => null,
            'is_ip_locked_out' => false,
        ];

        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (!$this->isLockoutEnabled($enabledKey)) {
            return $lockoutInfo;
        }

        // 現在の失敗回数を取得
        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow($timeWindowKey)
        );

        $maxAttempts = $this->getMaxAttempts($maxAttemptsKey);

        // ロックアウト状態をチェック
        if ($failedAttempts >= $maxAttempts) {
            $lockoutInfo['is_locked_out'] = true;
            $lockoutInfo['lockout_minutes'] = $this->getLockoutRemainingMinutes($identifier, $settings);
        } else {
            $lockoutInfo['remaining_attempts'] = $maxAttempts - $failedAttempts;
        }

        // IPアドレスベースのロックアウトもチェック
        $lockoutInfo['is_ip_locked_out'] = $this->isIpLockedOut($request->ip(), $settings);

        return $lockoutInfo;
    }

    /**
     * ロックアウト状態の詳細情報を取得
     *
     * @param string $identifier
     * @param string $ipAddress
     * @param array $settings 設定配列（オプション）
     * @return array
     */
    public function getLockoutStatus(string $identifier, string $ipAddress, array $settings = []): array
    {
        return [
            'is_enabled' => $this->isLockoutEnabled($settings['enabled_key'] ?? 'login_attempt_limit_enabled'),
            'is_locked_out' => $this->isLockedOut($identifier, $settings),
            'is_ip_locked_out' => $this->isIpLockedOut($ipAddress, $settings),
            'remaining_minutes' => $this->getLockoutRemainingMinutes($identifier, $settings),
            'max_attempts' => $this->getMaxAttempts($settings['max_attempts_key'] ?? 'login_attempt_max_attempts'),
            'time_window' => $this->getTimeWindow($settings['time_window_key'] ?? 'login_attempt_time_window'),
            'lockout_duration' => $this->getLockoutDuration($settings['lockout_duration_key'] ?? 'login_attempt_lockout_duration'),
        ];
    }

    /**
     * 設定値を取得する抽象メソッド（実装クラスで定義）
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    abstract protected function getSetting(string $key, $default = null);

    /**
     * Boolean設定値を取得
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    protected function getBooleanSetting(string $key, bool $default = false): bool
    {
        return (bool) $this->getSetting($key, $default);
    }

    /**
     * Integer設定値を取得
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    protected function getIntegerSetting(string $key, int $default = 0): int
    {
        return (int) $this->getSetting($key, $default);
    }
}
