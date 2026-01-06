<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MemberLoginAttempt extends Model
{
    use HasFactory;

    protected $table = 'members_login_attempts';

    protected $fillable = [
        'identifier',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at',
        // 行動分析用カラム
        'login_hour',
        'login_day_of_week',
        'device_fingerprint',
        'country_code',
        'seconds_since_last_login',
        'failure_reason',
        'used_2fa',
        'two_fa_method',
        'from_trusted_device',
        'risk_score',
        'context',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
        'used_2fa' => 'boolean',
        'from_trusted_device' => 'boolean',
        'login_hour' => 'integer',
        'login_day_of_week' => 'integer',
        'seconds_since_last_login' => 'integer',
        'risk_score' => 'integer',
        'context' => 'array',
    ];

    // ========================================
    // 失敗理由の定数
    // ========================================
    public const FAILURE_INVALID_PASSWORD = 'invalid_password';
    public const FAILURE_ACCOUNT_LOCKED = 'account_locked';
    public const FAILURE_ACCOUNT_DISABLED = 'account_disabled';
    public const FAILURE_2FA_FAILED = '2fa_failed';
    public const FAILURE_2FA_EXPIRED = '2fa_expired';
    public const FAILURE_IP_BLOCKED = 'ip_blocked';
    public const FAILURE_CAPTCHA_FAILED = 'captcha_failed';
    public const FAILURE_UNKNOWN = 'unknown';

    /**
     * 指定した識別子（メールアドレス等）の失敗した試行回数を取得
     *
     * @param string $identifier
     * @param int $timeWindowMinutes
     * @return int
     */
    public static function getFailedAttemptsCount(string $identifier, int $timeWindowMinutes): int
    {
        $cutoffTime = Carbon::now()->subMinutes($timeWindowMinutes);

        return static::where('identifier', $identifier)
            ->where('successful', false)
            ->where('attempted_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 指定したIPアドレスの失敗した試行回数を取得
     *
     * @param string $ipAddress
     * @param int $timeWindowMinutes
     * @return int
     */
    public static function getFailedAttemptsCountByIp(string $ipAddress, int $timeWindowMinutes): int
    {
        $cutoffTime = Carbon::now()->subMinutes($timeWindowMinutes);

        return static::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 最後の失敗した試行時刻を取得
     *
     * @param string $identifier
     * @return Carbon|null
     */
    public static function getLastFailedAttempt(string $identifier): ?Carbon
    {
        $attempt = static::where('identifier', $identifier)
            ->where('successful', false)
            ->orderBy('attempted_at', 'desc')
            ->first();

        return $attempt ? $attempt->attempted_at : null;
    }

    /**
     * ログイン試行を記録
     *
     * @param string $identifier
     * @param string $ipAddress
     * @param string|null $userAgent
     * @param bool $successful
     * @return static
     */
    public static function recordAttempt(
        string $identifier,
        string $ipAddress,
        ?string $userAgent = null,
        bool $successful = false
    ): static {
        return static::create([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => Carbon::now(),
        ]);
    }

    /**
     * 成功したログイン後、過去の失敗記録をクリア
     *
     * @param string $identifier
     * @return void
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        static::where('identifier', $identifier)
            ->where('successful', false)
            ->delete();
    }

    /**
     * 古いログイン試行記録を削除（クリーンアップ用）
     *
     * @param int $daysOld
     * @return int
     */
    public static function cleanupOldAttempts(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return static::where('attempted_at', '<', $cutoffDate)->delete();
    }

    // ========================================
    // 行動分析用メソッド（β版 行動分析の基盤）
    // ========================================

    /**
     * 行動分析データ付きでログイン試行を記録
     */
    public static function recordAttemptWithBehavior(
        string $identifier,
        string $ipAddress,
        ?string $userAgent = null,
        bool $successful = false,
        array $behaviorData = []
    ): static {
        $now = Carbon::now();
        
        // 前回のログイン試行を取得
        $lastAttempt = static::where('identifier', $identifier)
            ->orderBy('attempted_at', 'desc')
            ->first();
        
        // 前回からの経過時間を計算
        $secondsSinceLast = null;
        if ($lastAttempt) {
            $secondsSinceLast = $now->diffInSeconds($lastAttempt->attempted_at);
        }

        return static::create([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => $now,
            // 行動分析データ
            'login_hour' => $behaviorData['login_hour'] ?? $now->hour,
            'login_day_of_week' => $behaviorData['login_day_of_week'] ?? $now->dayOfWeek,
            'device_fingerprint' => $behaviorData['device_fingerprint'] ?? null,
            'country_code' => $behaviorData['country_code'] ?? null,
            'seconds_since_last_login' => $behaviorData['seconds_since_last_login'] ?? $secondsSinceLast,
            'failure_reason' => $behaviorData['failure_reason'] ?? null,
            'used_2fa' => $behaviorData['used_2fa'] ?? false,
            'two_fa_method' => $behaviorData['two_fa_method'] ?? null,
            'from_trusted_device' => $behaviorData['from_trusted_device'] ?? false,
            'risk_score' => $behaviorData['risk_score'] ?? null,
            'context' => $behaviorData['context'] ?? null,
        ]);
    }

    /**
     * ユーザーの通常ログイン時間帯を取得（β版で行動分析に使用）
     * 
     * @param string $identifier
     * @param int $days 分析対象日数
     * @return array ['hours' => [時間帯 => 回数], 'peak_hour' => 最頻時間帯]
     */
    public static function getLoginHourPattern(string $identifier, int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $attempts = static::where('identifier', $identifier)
            ->where('successful', true)
            ->where('attempted_at', '>=', $cutoffDate)
            ->whereNotNull('login_hour')
            ->get();

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = 0;
        }

        foreach ($attempts as $attempt) {
            $hours[$attempt->login_hour]++;
        }

        $peakHour = array_search(max($hours), $hours);

        return [
            'hours' => $hours,
            'peak_hour' => $peakHour,
            'total_logins' => $attempts->count(),
        ];
    }

    /**
     * ユーザーの通常ログイン曜日を取得
     */
    public static function getLoginDayPattern(string $identifier, int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $attempts = static::where('identifier', $identifier)
            ->where('successful', true)
            ->where('attempted_at', '>=', $cutoffDate)
            ->whereNotNull('login_day_of_week')
            ->get();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[$i] = 0;
        }

        foreach ($attempts as $attempt) {
            $days[$attempt->login_day_of_week]++;
        }

        return [
            'days' => $days,
            'peak_day' => array_search(max($days), $days),
        ];
    }

    /**
     * ユーザーの既知デバイスフィンガープリントを取得
     */
    public static function getKnownDeviceFingerprints(string $identifier, int $days = 90): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        return static::where('identifier', $identifier)
            ->where('successful', true)
            ->where('attempted_at', '>=', $cutoffDate)
            ->whereNotNull('device_fingerprint')
            ->distinct()
            ->pluck('device_fingerprint')
            ->toArray();
    }

    /**
     * ユーザーの既知国コードを取得
     */
    public static function getKnownCountryCodes(string $identifier, int $days = 90): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        return static::where('identifier', $identifier)
            ->where('successful', true)
            ->where('attempted_at', '>=', $cutoffDate)
            ->whereNotNull('country_code')
            ->distinct()
            ->pluck('country_code')
            ->toArray();
    }

    /**
     * 現在のログインが異常かどうかを判定（β版で実装予定）
     * 
     * @param string $identifier
     * @param int $currentHour
     * @param string|null $deviceFingerprint
     * @param string|null $countryCode
     * @return array ['is_anomaly' => bool, 'reasons' => array, 'risk_score' => int]
     */
    public static function detectAnomaly(
        string $identifier,
        int $currentHour,
        ?string $deviceFingerprint = null,
        ?string $countryCode = null
    ): array {
        $reasons = [];
        $riskScore = 0;

        // 時間帯の異常検知
        $hourPattern = static::getLoginHourPattern($identifier);
        if ($hourPattern['total_logins'] >= 5) {
            // 過去のログインが5回以上ある場合のみ判定
            $hourCount = $hourPattern['hours'][$currentHour] ?? 0;
            $totalLogins = $hourPattern['total_logins'];
            $hourRatio = $hourCount / $totalLogins;
            
            if ($hourRatio < 0.05) {
                // この時間帯のログインが5%未満
                $reasons[] = 'unusual_login_hour';
                $riskScore += 20;
            }
        }

        // デバイスフィンガープリントの異常検知
        if ($deviceFingerprint) {
            $knownFingerprints = static::getKnownDeviceFingerprints($identifier);
            if (!empty($knownFingerprints) && !in_array($deviceFingerprint, $knownFingerprints)) {
                $reasons[] = 'unknown_device';
                $riskScore += 30;
            }
        }

        // 国コードの異常検知
        if ($countryCode) {
            $knownCountries = static::getKnownCountryCodes($identifier);
            if (!empty($knownCountries) && !in_array($countryCode, $knownCountries)) {
                $reasons[] = 'unknown_country';
                $riskScore += 40;
            }
        }

        return [
            'is_anomaly' => !empty($reasons),
            'reasons' => $reasons,
            'risk_score' => min($riskScore, 100),
        ];
    }

    /**
     * ユーザーの行動統計を取得
     */
    public static function getBehaviorStats(string $identifier, int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $attempts = static::where('identifier', $identifier)
            ->where('attempted_at', '>=', $cutoffDate)
            ->get();

        $successful = $attempts->where('successful', true);
        $failed = $attempts->where('successful', false);

        return [
            'total_attempts' => $attempts->count(),
            'successful_count' => $successful->count(),
            'failed_count' => $failed->count(),
            'success_rate' => $attempts->count() > 0 
                ? round($successful->count() / $attempts->count() * 100, 2) 
                : 0,
            'unique_ips' => $attempts->pluck('ip_address')->unique()->count(),
            'unique_devices' => $attempts->pluck('device_fingerprint')->filter()->unique()->count(),
            'unique_countries' => $attempts->pluck('country_code')->filter()->unique()->count(),
            'used_2fa_count' => $attempts->where('used_2fa', true)->count(),
            'from_trusted_device_count' => $attempts->where('from_trusted_device', true)->count(),
            'failure_reasons' => $failed->pluck('failure_reason')->filter()->countBy()->toArray(),
            'average_risk_score' => $attempts->whereNotNull('risk_score')->avg('risk_score'),
        ];
    }

    // ========================================
    // スコープ（行動分析用）
    // ========================================

    /**
     * 高リスクのログイン試行を取得
     */
    public function scopeHighRisk($query, int $minScore = 50)
    {
        return $query->where('risk_score', '>=', $minScore);
    }

    /**
     * 特定の時間帯のログイン試行を取得
     */
    public function scopeInHour($query, int $hour)
    {
        return $query->where('login_hour', $hour);
    }

    /**
     * 特定の曜日のログイン試行を取得
     */
    public function scopeOnDayOfWeek($query, int $dayOfWeek)
    {
        return $query->where('login_day_of_week', $dayOfWeek);
    }

    /**
     * 特定の国からのログイン試行を取得
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * 2FAを使用したログイン試行を取得
     */
    public function scopeUsed2fa($query)
    {
        return $query->where('used_2fa', true);
    }

    /**
     * 信頼済みデバイスからのログイン試行を取得
     */
    public function scopeFromTrustedDevice($query)
    {
        return $query->where('from_trusted_device', true);
    }
}
