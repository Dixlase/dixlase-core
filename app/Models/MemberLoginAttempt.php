<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        // Columns for behavior analysis
        'login_hour',
        'login_day_of_week',
        'device_fingerprint',
        'country_code',
        'seconds_since_last_login',
        'failure_reason',
        'used_two_fa',
        'two_fa_method',
        'from_trusted_device',
        'risk_score',
        'context',
        'member_id',
        'is_bot_suspected',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
        'used_two_fa' => 'boolean',
        'from_trusted_device' => 'boolean',
        'login_hour' => 'integer',
        'login_day_of_week' => 'integer',
        'seconds_since_last_login' => 'integer',
        'risk_score' => 'integer',
        'context' => 'array',
        'member_id' => 'integer',
        'is_bot_suspected' => 'boolean',
    ];

    // ========================================
    // Constants for failure reasons
    // ========================================
    public const FAILURE_INVALID_PASSWORD = 'invalid_password';

    public const FAILURE_ACCOUNT_LOCKED = 'account_locked';

    public const FAILURE_ACCOUNT_DISABLED = 'account_disabled';

    public const FAILURE_TWO_FA_FAILED = 'two_fa_failed';

    public const FAILURE_TWO_FA_EXPIRED = 'two_fa_expired';

    public const FAILURE_IP_BLOCKED = 'ip_blocked';

    public const FAILURE_CAPTCHA_FAILED = 'captcha_failed';

    public const FAILURE_UNKNOWN = 'unknown';

    public const FAILURE_BOT_DETECTED = 'bot_detected';

    public const FAILURE_RATE_LIMITED = 'rate_limited';

    /**
     * Get the number of failed attempts for the specified identifier (email address, etc.)
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
     * Get the number of failed attempts for the specified IP address
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
     * Get the last failed attempt time
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
     * Record login attempt
     */
    public static function recordAttempt(
        string $identifier,
        string $ipAddress,
        ?string $userAgent = null,
        bool $successful = false,
        ?int $memberId = null,
    ): static {
        return static::create([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => Carbon::now(),
            'member_id' => $memberId,
        ]);
    }

    /**
     * Clear past failure records after successful login
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        static::where('identifier', $identifier)
            ->where('successful', false)
            ->delete();
    }

    /**
     * Delete old login attempt records (for cleanup)
     */
    public static function cleanupOldAttempts(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return static::where('attempted_at', '<', $cutoffDate)->delete();
    }

    // ========================================
    // Methods for behavior analysis (beta version behavior analysis foundation)
    // ========================================

    /**
     * Record login attempt with behavior analysis data
     */
    public static function recordAttemptWithBehavior(
        string $identifier,
        string $ipAddress,
        ?string $userAgent = null,
        bool $successful = false,
        array $behaviorData = []
    ): static {
        $now = Carbon::now();

        // Get the previous login attempt
        $lastAttempt = static::where('identifier', $identifier)
            ->orderBy('attempted_at', 'desc')
            ->first();

        // Calculate elapsed time since the previous attempt
        $secondsSinceLast = null;
        if ($lastAttempt) {
            $secondsSinceLast = (int) $now->diffInSeconds($lastAttempt->attempted_at, absolute: true);
        }

        return static::create([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => $now,
            // Behavior analysis data
            'login_hour' => $behaviorData['login_hour'] ?? $now->hour,
            'login_day_of_week' => $behaviorData['login_day_of_week'] ?? $now->dayOfWeek,
            'device_fingerprint' => $behaviorData['device_fingerprint'] ?? null,
            'country_code' => $behaviorData['country_code'] ?? null,
            'seconds_since_last_login' => $behaviorData['seconds_since_last_login'] ?? $secondsSinceLast,
            'failure_reason' => $behaviorData['failure_reason'] ?? null,
            'used_two_fa' => $behaviorData['used_two_fa'] ?? false,
            'two_fa_method' => $behaviorData['two_fa_method'] ?? null,
            'from_trusted_device' => $behaviorData['from_trusted_device'] ?? false,
            'risk_score' => $behaviorData['risk_score'] ?? null,
            'context' => $behaviorData['context'] ?? null,
            'member_id' => $behaviorData['member_id'] ?? null,
            'is_bot_suspected' => $behaviorData['is_bot_suspected'] ?? false,
        ]);
    }

    /**
     * Get user's typical login time periods (used for behavior analysis in beta version)
     *
     * @param  int  $days  Number of days to analyze
     * @return array ['hours' => [hour => count], 'peak_hour' => most frequent hour]
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
     * Get user's typical login days of the week
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
     * Get user's known device fingerprints
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
     * Get user's known country codes
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
     * Determine whether the current login is anomalous (planned for implementation in beta version)
     *
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

        // Detect time zone anomaly
        $hourPattern = static::getLoginHourPattern($identifier);
        if ($hourPattern['total_logins'] >= 5) {
            // Only evaluate if there are 5 or more past logins
            $hourCount = $hourPattern['hours'][$currentHour] ?? 0;
            $totalLogins = $hourPattern['total_logins'];
            $hourRatio = $hourCount / $totalLogins;

            if ($hourRatio < 0.05) {
                // Logins in this time zone are less than 5%
                $reasons[] = 'unusual_login_hour';
                $riskScore += 20;
            }
        }

        // Detect device fingerprint anomaly
        if ($deviceFingerprint) {
            $knownFingerprints = static::getKnownDeviceFingerprints($identifier);
            if (! empty($knownFingerprints) && ! in_array($deviceFingerprint, $knownFingerprints)) {
                $reasons[] = 'unknown_device';
                $riskScore += 30;
            }
        }

        // Detect country code anomaly
        if ($countryCode) {
            $knownCountries = static::getKnownCountryCodes($identifier);
            if (! empty($knownCountries) && ! in_array($countryCode, $knownCountries)) {
                $reasons[] = 'unknown_country';
                $riskScore += 40;
            }
        }

        return [
            'is_anomaly' => ! empty($reasons),
            'reasons' => $reasons,
            'risk_score' => min($riskScore, 100),
        ];
    }

    /**
     * Get user behavior statistics
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
            'used_two_fa_count' => $attempts->where('used_two_fa', true)->count(),
            'from_trusted_device_count' => $attempts->where('from_trusted_device', true)->count(),
            'failure_reasons' => $failed->pluck('failure_reason')->filter()->countBy()->toArray(),
            'average_risk_score' => $attempts->whereNotNull('risk_score')->avg('risk_score'),
        ];
    }

    // ========================================
    // Scopes (for behavior analysis)
    // ========================================

    /**
     * Get high-risk login attempts
     */
    public function scopeHighRisk($query, int $minScore = 50)
    {
        return $query->where('risk_score', '>=', $minScore);
    }

    /**
     * Get login attempts for a specific time zone
     */
    public function scopeInHour($query, int $hour)
    {
        return $query->where('login_hour', $hour);
    }

    /**
     * Get login attempts for a specific day of week
     */
    public function scopeOnDayOfWeek($query, int $dayOfWeek)
    {
        return $query->where('login_day_of_week', $dayOfWeek);
    }

    /**
     * Get login attempts from a specific country
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Get login attempts using 2FA
     */
    public function scopeUsedTwoFa($query)
    {
        return $query->where('used_two_fa', true);
    }

    /**
     * Get login attempts from trusted devices
     */
    public function scopeFromTrustedDevice($query)
    {
        return $query->where('from_trusted_device', true);
    }

    /**
     * Bot suspected attempts only
     */
    public function scopeBotSuspected($query)
    {
        return $query->where('is_bot_suspected', true);
    }

    /**
     * Filter by member ID
     */
    public function scopeForMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }
}
