<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン試行制限の共通ロジックを提供するTrait
 *
 * このTraitは、メンバーとユーザーのログイン試行制限処理で共通する
 * ロジックを提供します。
 *
 * 使用するコントローラーは以下の抽象メソッドを実装する必要があります：
 * - getLoginAttemptModelClass(): ログイン試行モデルのクラス名を返す
 * - getSetting(): 設定値を取得
 */
trait LoginLockoutTrait
{
    /**
     * ログイン試行モデルのクラス名を取得
     */
    abstract protected function getLoginAttemptModelClass(): string;

    /**
     * ログイン試行制限が有効かどうかを確認
     *
     * @param  string  $settingKey  デフォルト: 'login_attempt_limit_enabled'
     */
    public function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, false);
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @param  string  $settingKey  デフォルト: 'lockout_notification_enabled'
     */
    public function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled'): bool
    {
        return $this->getBooleanSetting($settingKey, true);
    }

    /**
     * 最大試行回数を取得
     *
     * @param  string  $settingKey  デフォルト: 'login_attempt_max_attempts'
     */
    public function getMaxAttempts(string $settingKey = 'login_attempt_max_attempts'): int
    {
        return $this->getIntegerSetting($settingKey, 5);
    }

    /**
     * 時間窓（分）を取得
     *
     * @param  string  $settingKey  デフォルト: 'login_attempt_time_window'
     */
    public function getTimeWindow(string $settingKey = 'login_attempt_time_window'): int
    {
        return $this->getIntegerSetting($settingKey, 15);
    }

    /**
     * ロックアウト時間（分）を取得
     *
     * @param  string  $settingKey  デフォルト: 'login_attempt_lockout_duration'
     */
    public function getLockoutDuration(string $settingKey = 'login_attempt_lockout_duration'): int
    {
        return $this->getIntegerSetting($settingKey, 30);
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     *
     * @param  array  $settings  設定配列（オプション）
     */
    public function isLockedOut(string $identifier, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (! $this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $failedAttempts = $modelClass::getFailedAttemptsCount(
            $identifier,
            $this->getTimeWindow($timeWindowKey)
        );

        return $failedAttempts >= $this->getMaxAttempts($maxAttemptsKey);
    }

    /**
     * IPアドレスがロックアウトされているかを確認
     *
     * @param  array  $settings  設定配列（オプション）
     */
    public function isIpLockedOut(string $ipAddress, array $settings = []): bool
    {
        $enabledKey = $settings['enabled_key'] ?? 'login_attempt_limit_enabled';
        $maxAttemptsKey = $settings['max_attempts_key'] ?? 'login_attempt_max_attempts';
        $timeWindowKey = $settings['time_window_key'] ?? 'login_attempt_time_window';

        if (! $this->isLockoutEnabled($enabledKey)) {
            return false;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $failedAttempts = $modelClass::getFailedAttemptsCountByIp(
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
     * @param  array  $settings  設定配列（オプション）
     */
    public function getLockoutRemainingMinutes(string $identifier, array $settings = []): ?int
    {
        $lockoutDurationKey = $settings['lockout_duration_key'] ?? 'login_attempt_lockout_duration';

        if (! $this->isLockedOut($identifier, $settings)) {
            return null;
        }

        $modelClass = $this->getLoginAttemptModelClass();
        $lastFailedAttempt = $modelClass::getLastFailedAttempt($identifier);
        if (! $lastFailedAttempt) {
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
     * @return MemberLoginAttempt
     */
    public function recordLoginAttempt(Request $request, string $identifier, bool $successful = false)
    {
        $modelClass = $this->getLoginAttemptModelClass();

        return $modelClass::recordAttempt(
            $identifier,
            $request->ip(),
            $request->userAgent(),
            $successful
        );
    }

    /**
     * 成功したログイン後の処理
     */
    public function handleSuccessfulLogin(string $identifier): void
    {
        $modelClass = $this->getLoginAttemptModelClass();

        // 成功したログインを記録
        $modelClass::recordAttempt(
            $identifier,
            request()->ip(),
            request()->userAgent(),
            true
        );

        // 失敗した試行記録をクリア
        $modelClass::clearFailedAttempts($identifier);
    }

    /**
     * 失敗したログイン後の処理
     *
     * @param  array  $settings  設定配列（オプション）
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

        if (! $this->isLockoutEnabled($enabledKey)) {
            return $lockoutInfo;
        }

        $modelClass = $this->getLoginAttemptModelClass();

        // 現在の失敗回数を取得
        $failedAttempts = $modelClass::getFailedAttemptsCount(
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
     * @param  array  $settings  設定配列（オプション）
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
     * @param  mixed  $default
     * @return mixed
     */
    abstract protected function getSetting(string $key, $default = null);

    /**
     * Boolean設定値を取得
     */
    protected function getBooleanSetting(string $key, bool $default = false): bool
    {
        return (bool) $this->getSetting($key, $default);
    }

    /**
     * Integer設定値を取得
     */
    protected function getIntegerSetting(string $key, int $default = 0): int
    {
        return (int) $this->getSetting($key, $default);
    }
}
