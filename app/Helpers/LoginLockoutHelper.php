<?php

namespace App\Helpers;

use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 */
class LoginLockoutHelper
{
    /**
     * ログイン試行制限が有効かどうかを確認
     *
     * @param  mixed  $settingSource  設定ソース（SecuritySetting::class など）
     */
    public static function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            return (bool) $settingSource::getValue($settingKey, false);
        }

        return (bool) SecuritySetting::getValue($settingKey, false);
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @param  mixed  $settingSource  設定ソース（SecuritySetting::class など）
     */
    public static function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            return (bool) $settingSource::getValue($settingKey, true);
        }

        return (bool) SecuritySetting::getValue($settingKey, true);
    }

    /**
     * ログイン試行制限の設定を取得
     *
     * @param  array  $settingKeys  設定キーの配列
     * @param  mixed  $settingSource  設定ソース（SecuritySetting::class など）
     */
    public static function getLockoutSettings(array $settingKeys = [], $settingSource = null): array
    {
        $defaultKeys = [
            'enabled_key' => 'login_attempt_limit_enabled',
            'max_attempts_key' => 'login_attempt_max_attempts',
            'max_attempts_ip_key' => 'login_attempt_max_attempts_ip',
            'time_window_key' => 'login_attempt_time_window',
            'lockout_duration_key' => 'login_attempt_lockout_duration',
            'notification_enabled_key' => 'login_attempt_lockout_notification_enabled',
        ];

        $keys = array_merge($defaultKeys, $settingKeys);
        $source = $settingSource ?: SecuritySetting::class;

        return [
            'enabled' => (bool) $source::getValue($keys['enabled_key'], false),
            'max_attempts' => (int) $source::getValue($keys['max_attempts_key'], 5),
            'max_attempts_ip' => (int) $source::getValue($keys['max_attempts_ip_key'], null),
            'time_window' => (int) $source::getValue($keys['time_window_key'], 15),
            'lockout_duration' => (int) $source::getValue($keys['lockout_duration_key'], 30),
            'notification_enabled' => (bool) $source::getValue($keys['notification_enabled_key'], true),
        ];
    }

    /**
     * ログイン試行を記録し、ロックアウト状態を確認
     *
     * @param  array  $settings  設定配列
     * @param  mixed  $settingSource  設定ソース
     */
    public static function recordAndCheckLockout(
        Request $request,
        string $identifier,
        bool $successful = false,
        array $settings = [],
        $settingSource = null
    ): array {
        // ログイン試行を記録
        MemberLoginAttempt::recordAttempt(
            $identifier,
            $request->ip(),
            $request->userAgent(),
            $successful
        );

        if ($successful) {
            // 成功時は失敗記録をクリア
            MemberLoginAttempt::clearFailedAttempts($identifier);

            return ['success' => true];
        }

        // 失敗時のロックアウト状態をチェック
        return static::checkLockoutStatus($request, $identifier, $settings, $settingSource);
    }

    /**
     * ロックアウト状態をチェック
     *
     * @param  array  $settings  設定配列
     * @param  mixed  $settingSource  設定ソース
     */
    public static function checkLockoutStatus(
        Request $request,
        string $identifier,
        array $settings = [],
        $settingSource = null
    ): array {
        $lockoutSettings = static::getLockoutSettings($settings, $settingSource);

        $lockoutInfo = [
            'is_locked_out' => false,
            'remaining_attempts' => null,
            'lockout_minutes' => null,
            'is_ip_locked_out' => false,
            'settings' => $lockoutSettings,
        ];

        if (! $lockoutSettings['enabled']) {
            return $lockoutInfo;
        }

        // 時間窓内の失敗回数をチェック
        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $lockoutSettings['time_window']
        );

        if ($failedAttempts >= $lockoutSettings['max_attempts']) {
            // 失敗回数が上限に達した場合、ロックアウト期間をチェック
            $remainingLockoutMinutes = static::getLockoutRemainingMinutes(
                $identifier,
                $lockoutSettings['lockout_duration']
            );

            if ($remainingLockoutMinutes === null) {
                // 失敗記録がない場合（通常ここには来ない）
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
            } elseif ($remainingLockoutMinutes === 0) {
                // ロックアウト期間が終了している場合は解除
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
            } else {
                // ロックアウト期間中
                $lockoutInfo['is_locked_out'] = true;
                $lockoutInfo['lockout_minutes'] = $remainingLockoutMinutes;

                // ロックアウト通知を送信（重複送信を防ぐ）
                if ($lockoutSettings['notification_enabled']) {
                    $notificationKey = 'lockout_notification_sent_'.md5($identifier);
                    $lastNotificationTime = session($notificationKey);

                    // 最後の通知から30分以上経過している場合のみ再送信
                    if (! $lastNotificationTime || Carbon::parse($lastNotificationTime)->addMinutes(30)->isPast()) {
                        // 通知送信を試行し、成功した場合のみセッションに記録
                        $sent = static::sendLockoutNotification($identifier, $request, $lockoutSettings);
                        if ($sent) {
                            session([$notificationKey => Carbon::now()->toDateTimeString()]);
                        } else {
                            Log::warning('LoginLockout: Notification failed, not recorded in session', [
                                'identifier' => $identifier,
                            ]);
                        }
                    } else {
                    }
                } else {
                }
            }
        } else {
            // 失敗回数が上限未満の場合は正常状態
            $lockoutInfo['is_locked_out'] = false;
            $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'] - $failedAttempts;
            $lockoutInfo['lockout_minutes'] = 0;
        }

        // IPアドレスベースのロックアウトもチェック
        $ipFailedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $request->ip(),
            $lockoutSettings['time_window']
        );
        // login_attempt_max_attempts_ip設定を優先的に使用、なければmax_attempts * 2
        $maxAttemptsForIp = $lockoutSettings['max_attempts_ip'] ?? ($lockoutSettings['max_attempts'] * 2);
        $lockoutInfo['is_ip_locked_out'] = $ipFailedAttempts >= $maxAttemptsForIp;

        return $lockoutInfo;
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     */
    public static function getLockoutRemainingMinutes(string $identifier, int $lockoutDuration): ?int
    {
        $lastFailedAttempt = MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (! $lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->copy()->addMinutes($lockoutDuration);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($lockoutUntil)) {
            return 0; // ロックアウト期間終了
        }

        // 秒単位で計算して分に切り上げ
        $remainingSeconds = $now->diffInSeconds($lockoutUntil);

        return (int) ceil($remainingSeconds / 60);
    }

    /**
     * ロックアウト通知を送信
     *
     * @return bool 送信成功時true、失敗時false
     */
    public static function sendLockoutNotification(string $identifier, Request $request, array $settings): bool
    {
        try {
            // 通知先メールアドレスを取得
            $notificationEmail = \App\Models\BaseSetting::getValue('notification_email');

            if (empty($notificationEmail)) {
                Log::warning('ロックアウト通知: 管理者メールアドレスが設定されていません');

                return false;
            }

            // メールサーバーが設定済みかチェック
            if (! \App\Services\MailServerValidatorService::isMailServerTested()) {
                Log::warning('ロックアウト通知: メールサーバーが設定されていません');

                return false;
            }

            $subject = __('mail.lockout_notification.subject');
            $details = [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                'max_attempts' => $settings['max_attempts'],
                'time_window' => $settings['time_window'],
                'lockout_duration' => $settings['lockout_duration'],
            ];

            // Mailableクラスを使用してメール送信
            $lockoutMail = new \App\Mail\LockoutNotificationMail($details);

            \Mail::to($notificationEmail)->send($lockoutMail);

            Log::info('ロックアウト通知を送信しました', [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'notification_email' => $notificationEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ロックアウト通知の送信に失敗しました', [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 期限切れのログイン試行記録をクリーンアップ
     *
     * @param  int  $days  保持日数（デフォルト: 30日）
     * @return int 削除された記録数
     */
    public static function cleanupExpiredAttempts(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return MemberLoginAttempt::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * 指定した識別子の失敗記録をクリア
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        MemberLoginAttempt::clearFailedAttempts($identifier);
    }

    /**
     * 全ての失敗記録をクリア
     *
     * @return int 削除された記録数
     */
    public static function clearAllFailedAttempts(): int
    {
        return MemberLoginAttempt::where('successful', false)->delete();
    }

    /**
     * ロックアウト状態の詳細情報を取得
     *
     * @param  array  $settings  設定配列
     * @param  mixed  $settingSource  設定ソース
     */
    public static function getLockoutStatusDetails(
        string $identifier,
        string $ipAddress,
        array $settings = [],
        $settingSource = null
    ): array {
        $lockoutSettings = static::getLockoutSettings($settings, $settingSource);

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $lockoutSettings['time_window']
        );

        $ipFailedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $lockoutSettings['time_window']
        );

        return [
            'is_enabled' => $lockoutSettings['enabled'],
            'is_locked_out' => $failedAttempts >= $lockoutSettings['max_attempts'],
            'is_ip_locked_out' => $ipFailedAttempts >= ($lockoutSettings['max_attempts'] * 2),
            'failed_attempts' => $failedAttempts,
            'ip_failed_attempts' => $ipFailedAttempts,
            'remaining_attempts' => max(0, $lockoutSettings['max_attempts'] - $failedAttempts),
            'remaining_minutes' => static::getLockoutRemainingMinutes($identifier, $lockoutSettings['lockout_duration']),
            'settings' => $lockoutSettings,
        ];
    }

    /**
     * ロックアウトエラーメッセージを生成
     *
     * @param  string  $context  コンテキスト（admin, user など）
     */
    public static function generateLockoutMessage(array $lockoutInfo, string $context = 'admin'): string
    {
        if ($lockoutInfo['is_ip_locked_out']) {
            return __("auth.lockout.ip_locked_out.{$context}");
        }

        if ($lockoutInfo['is_locked_out']) {
            $minutes = $lockoutInfo['lockout_minutes'] ?? 0;

            return __("auth.lockout.account_locked_out.{$context}", ['minutes' => $minutes]);
        }

        if (isset($lockoutInfo['remaining_attempts']) && $lockoutInfo['remaining_attempts'] > 0) {
            return __("auth.lockout.remaining_attempts.{$context}", [
                'attempts' => $lockoutInfo['remaining_attempts'],
            ]);
        }

        return __("auth.failed.{$context}");
    }
}
