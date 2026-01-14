<?php

namespace App\Helpers;

use App\Models\MemberLoginAttempt;
use App\Models\MemberSetting;
use App\Services\SystemNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginLockoutHelper
{
    /**
     * ログイン試行制限が有効かどうかを確認
     *
     * @param string $settingKey
     * @param mixed $settingSource 設定ソース（MemberSetting::class など）
     * @return bool
     */
    public static function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            return (bool) $settingSource::getValue($settingKey, false);
        }
        return (bool) MemberSetting::getValue($settingKey, false);
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     *
     * @param string $settingKey
     * @param mixed $settingSource 設定ソース（MemberSetting::class など）
     * @return bool
     */
    public static function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            $value = $settingSource::getValue($settingKey, true);
            Log::info('LoginLockoutHelper::isNotificationEnabled (custom source)', [
                'setting_key' => $settingKey,
                'source' => get_class($settingSource),
                'raw_value' => $value,
                'boolean_value' => (bool) $value
            ]);
            return (bool) $value;
        }
        
        $value = MemberSetting::getValue($settingKey, true);
        Log::info('LoginLockoutHelper::isNotificationEnabled (MemberSetting)', [
            'setting_key' => $settingKey,
            'raw_value' => $value,
            'boolean_value' => (bool) $value
        ]);
        return (bool) $value;
    }

    /**
     * ログイン試行制限の設定を取得
     *
     * @param array $settingKeys 設定キーの配列
     * @param mixed $settingSource 設定ソース（MemberSetting::class など）
     * @return array
     */
    public static function getLockoutSettings(array $settingKeys = [], $settingSource = null): array
    {
        $defaultKeys = [
            'enabled_key' => 'login_attempt_limit_enabled',
            'max_attempts_key' => 'login_attempt_max_attempts',
            'time_window_key' => 'login_attempt_time_window',
            'lockout_duration_key' => 'login_attempt_lockout_duration',
            'notification_enabled_key' => 'lockout_notification_enabled',
        ];

        $keys = array_merge($defaultKeys, $settingKeys);
        $source = $settingSource ?: MemberSetting::class;

        return [
            'enabled' => (bool) $source::getValue($keys['enabled_key'], false),
            'max_attempts' => (int) $source::getValue($keys['max_attempts_key'], 5),
            'time_window' => (int) $source::getValue($keys['time_window_key'], 15),
            'lockout_duration' => (int) $source::getValue($keys['lockout_duration_key'], 30),
            'notification_enabled' => (bool) $source::getValue($keys['notification_enabled_key'], true),
        ];
    }

    /**
     * ログイン試行を記録し、ロックアウト状態を確認
     *
     * @param Request $request
     * @param string $identifier
     * @param bool $successful
     * @param array $settings 設定配列
     * @param mixed $settingSource 設定ソース
     * @return array
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
     * @param Request $request
     * @param string $identifier
     * @param array $settings 設定配列
     * @param mixed $settingSource 設定ソース
     * @return array
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

        if (!$lockoutSettings['enabled']) {
            return $lockoutInfo;
        }

        // 時間窓内の失敗回数をチェック
        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $lockoutSettings['time_window']
        );

        // デバッグログ出力
        \Log::info('LoginLockout Debug', [
            'identifier' => $identifier,
            'failed_attempts' => $failedAttempts,
            'max_attempts' => $lockoutSettings['max_attempts'],
            'time_window' => $lockoutSettings['time_window'],
            'lockout_duration' => $lockoutSettings['lockout_duration'],
            'condition_check' => $failedAttempts >= $lockoutSettings['max_attempts']
        ]);

        if ($failedAttempts >= $lockoutSettings['max_attempts']) {
            // 失敗回数が上限に達した場合、ロックアウト期間をチェック
            $remainingLockoutMinutes = static::getLockoutRemainingMinutes(
                $identifier,
                $lockoutSettings['lockout_duration']
            );

            \Log::info('LoginLockout Remaining Time Check', [
                'identifier' => $identifier,
                'remaining_minutes' => $remainingLockoutMinutes,
                'last_attempt' => MemberLoginAttempt::getLastFailedAttempt($identifier)?->format('Y-m-d H:i:s')
            ]);

            if ($remainingLockoutMinutes === null) {
                // 失敗記録がない場合（通常ここには来ない）
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
                \Log::info('LoginLockout Result: No records found');
            } else if ($remainingLockoutMinutes === 0) {
                // ロックアウト期間が終了している場合は解除
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
                \Log::info('LoginLockout Result: Period expired, unlocked');
            } else {
                // ロックアウト期間中
                $lockoutInfo['is_locked_out'] = true;
                $lockoutInfo['lockout_minutes'] = $remainingLockoutMinutes;
                \Log::info('LoginLockout Result: Still locked', ['remaining_minutes' => $remainingLockoutMinutes]);

                // ロックアウト通知を送信（重複送信を防ぐ）
                if ($lockoutSettings['notification_enabled']) {
                    $notificationKey = 'lockout_notification_sent_' . md5($identifier);
                    $lastNotificationTime = session($notificationKey);
                    
                    // 最後の通知から30分以上経過している場合のみ再送信
                    if (!$lastNotificationTime || Carbon::parse($lastNotificationTime)->addMinutes(30)->isPast()) {
                        Log::info('LoginLockout: Sending notification', [
                            'identifier' => $identifier,
                            'notification_enabled' => $lockoutSettings['notification_enabled'],
                            'last_notification' => $lastNotificationTime,
                            'settings' => $lockoutSettings
                        ]);
                        static::sendLockoutNotification($identifier, $request, $lockoutSettings);
                        session([$notificationKey => Carbon::now()->toDateTimeString()]);
                    } else {
                        Log::info('LoginLockout: Notification already sent recently', [
                            'identifier' => $identifier,
                            'last_notification' => $lastNotificationTime
                        ]);
                    }
                } else {
                    Log::info('LoginLockout: Notification disabled', [
                        'identifier' => $identifier,
                        'notification_enabled' => $lockoutSettings['notification_enabled']
                    ]);
                }
            }
        } else {
            // 失敗回数が上限未満の場合は正常状態
            $lockoutInfo['is_locked_out'] = false;
            $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'] - $failedAttempts;
            $lockoutInfo['lockout_minutes'] = 0;
            \Log::info('LoginLockout Result: Normal state', [
                'remaining_attempts' => $lockoutInfo['remaining_attempts']
            ]);
        }

        // IPアドレスベースのロックアウトもチェック
        $ipFailedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $request->ip(),
            $lockoutSettings['time_window']
        );
        $maxAttemptsForIp = $lockoutSettings['max_attempts'] * 2;
        $lockoutInfo['is_ip_locked_out'] = $ipFailedAttempts >= $maxAttemptsForIp;

        return $lockoutInfo;
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     *
     * @param string $identifier
     * @param int $lockoutDuration
     * @return int|null
     */
    public static function getLockoutRemainingMinutes(string $identifier, int $lockoutDuration): ?int
    {
        $lastFailedAttempt = MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (!$lastFailedAttempt) {
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
     * @param string $identifier
     * @param Request $request
     * @param array $settings
     * @return void
     */
    public static function sendLockoutNotification(string $identifier, Request $request, array $settings): void
    {
        Log::info('LoginLockout: sendLockoutNotification called', [
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'settings' => $settings
        ]);
        
        try {
            // 通知先メールアドレスを取得
            $notificationEmail = \App\Models\BaseSetting::getValue('notification_email');
            
            if (empty($notificationEmail)) {
                Log::warning('ロックアウト通知: 管理者メールアドレスが設定されていません');
                return;
            }

            // メールサーバーが設定済みかチェック（SystemNotificationServiceの一部機能を借用）
            $systemNotificationService = new SystemNotificationService();
            if (!$systemNotificationService->isMailServerConfigured()) {
                Log::warning('ロックアウト通知: メールサーバーが設定されていません');
                return;
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
        } catch (\Exception $e) {
            Log::error('ロックアウト通知の送信に失敗しました', [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 期限切れのログイン試行記録をクリーンアップ
     *
     * @param int $days 保持日数（デフォルト: 30日）
     * @return int 削除された記録数
     */
    public static function cleanupExpiredAttempts(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        return MemberLoginAttempt::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * 指定した識別子の失敗記録をクリア
     *
     * @param string $identifier
     * @return void
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
     * @param string $identifier
     * @param string $ipAddress
     * @param array $settings 設定配列
     * @param mixed $settingSource 設定ソース
     * @return array
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
     * @param array $lockoutInfo
     * @param string $context コンテキスト（admin, user など）
     * @return string
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
                'attempts' => $lockoutInfo['remaining_attempts']
            ]);
        }

        return __("auth.failed.{$context}");
    }
}
