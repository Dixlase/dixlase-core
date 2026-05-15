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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services;

use App\Models\MemberLoginAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Login behavior analysis service
 *
 * Used as the foundation for behavior analysis features in beta
 * Handles context collection and anomaly detection during login
 */
class LoginBehaviorService
{
    /**
     * Collect login context from request
     */
    public function collectLoginContext(Request $request): array
    {
        $now = Carbon::now();

        return [
            'login_hour' => $now->hour,
            'login_day_of_week' => $now->dayOfWeek,
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
            'country_code' => $this->getCountryCode($request),
        ];
    }

    /**
     * Generate device fingerprint
     *
     * Hash of UserAgent + Accept-Language + Accept-Encoding
     */
    public function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent() ?? '',
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
            $request->header('Accept', ''),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Get country code from IP address
     *
     * GeoIP library integration planned for beta
     * Currently returns null (placeholder)
     */
    public function getCountryCode(Request $request): ?string
    {
        // TODO: Integrate GeoIP2/MaxMind in beta
        // Currently returns null as a placeholder
        return null;
    }

    /**
     * Record login attempt (with behavior analysis data)
     */
    public function recordLoginAttempt(
        string $identifier,
        Request $request,
        bool $successful,
        array $additionalData = []
    ): MemberLoginAttempt {
        $context = $this->collectLoginContext($request);

        // Resolve member_id from identifier
        if (! isset($additionalData['member_id'])) {
            $member = \App\Models\Member::where('email', $identifier)
                ->orWhere('account_name', $identifier)
                ->first();
            $additionalData['member_id'] = $member?->id;
        }

        // Detect bot behavior
        if (! isset($additionalData['is_bot_suspected'])) {
            $botResult = $this->detectBotBehavior($request, $identifier);
            $additionalData['is_bot_suspected'] = $botResult['is_bot_suspected'];

            if ($botResult['is_bot_suspected']) {
                $additionalData['context'] = array_merge(
                    $additionalData['context'] ?? [],
                    ['bot_signals' => $botResult['signals'], 'bot_score' => $botResult['bot_score']]
                );
            }
        }

        $behaviorData = array_merge($context, $additionalData);

        return MemberLoginAttempt::recordAttemptWithBehavior(
            $identifier,
            $request->ip(),
            $request->userAgent(),
            $successful,
            $behaviorData
        );
    }

    /**
     * Record login success
     */
    public function recordSuccessfulLogin(
        string $identifier,
        Request $request,
        array $additionalData = []
    ): MemberLoginAttempt {
        return $this->recordLoginAttempt($identifier, $request, true, $additionalData);
    }

    /**
     * Record login failure
     */
    public function recordFailedLogin(
        string $identifier,
        Request $request,
        string $failureReason = MemberLoginAttempt::FAILURE_UNKNOWN,
        array $additionalData = []
    ): MemberLoginAttempt {
        $additionalData['failure_reason'] = $failureReason;

        return $this->recordLoginAttempt($identifier, $request, false, $additionalData);
    }

    /**
     * Determine if current login is anomalous
     */
    public function detectAnomaly(string $identifier, Request $request): array
    {
        $context = $this->collectLoginContext($request);

        return MemberLoginAttempt::detectAnomaly(
            $identifier,
            $context['login_hour'],
            $context['device_fingerprint'],
            $context['country_code']
        );
    }

    /**
     * Get user's behavior profile
     */
    public function getBehaviorProfile(string $identifier, int $days = 30): array
    {
        return [
            'stats' => MemberLoginAttempt::getBehaviorStats($identifier, $days),
            'hour_pattern' => MemberLoginAttempt::getLoginHourPattern($identifier, $days),
            'day_pattern' => MemberLoginAttempt::getLoginDayPattern($identifier, $days),
            'known_devices' => count(MemberLoginAttempt::getKnownDeviceFingerprints($identifier, $days * 3)),
            'known_countries' => MemberLoginAttempt::getKnownCountryCodes($identifier, $days * 3),
        ];
    }

    /**
     * Calculate risk score (to be extended in beta)
     *
     * @return int Risk score from 0-100
     */
    public function calculateRiskScore(string $identifier, Request $request): int
    {
        $anomaly = $this->detectAnomaly($identifier, $request);
        $riskScore = $anomaly['risk_score'];

        // Additional risk factors (to be extended in beta)
        // - Consecutive failure count
        // - Multiple IPs in short time
        // - VPN/Tor detection
        // - Brute force detection

        // Add risk based on consecutive failure count
        $recentFailures = MemberLoginAttempt::getFailedAttemptsCount($identifier, 60);
        if ($recentFailures >= 3) {
            $riskScore += min($recentFailures * 5, 30);
        }

        return min($riskScore, 100);
    }

    /**
     * Get high-risk login list
     */
    public function getHighRiskLogins(int $hours = 24, int $minScore = 50, int $limit = 100)
    {
        return MemberLoginAttempt::where('attempted_at', '>=', Carbon::now()->subHours($hours))
            ->highRisk($minScore)
            ->orderByDesc('attempted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get high-risk login history for specific user
     */
    public function getHighRiskLoginsForUser(string $identifier, int $days = 30, int $minScore = 50)
    {
        return MemberLoginAttempt::where('identifier', $identifier)
            ->where('attempted_at', '>=', Carbon::now()->subDays($days))
            ->highRisk($minScore)
            ->orderByDesc('attempted_at')
            ->get();
    }

    /**
     * Generate summary report of login behavior
     */
    public function generateBehaviorReport(int $days = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $attempts = MemberLoginAttempt::where('attempted_at', '>=', $cutoffDate)->get();

        return [
            'period_days' => $days,
            'total_attempts' => $attempts->count(),
            'successful_count' => $attempts->where('successful', true)->count(),
            'failed_count' => $attempts->where('successful', false)->count(),
            'unique_users' => $attempts->pluck('identifier')->unique()->count(),
            'unique_ips' => $attempts->pluck('ip_address')->unique()->count(),
            'high_risk_count' => $attempts->where('risk_score', '>=', 50)->count(),
            'used_two_fa_count' => $attempts->where('used_two_fa', true)->count(),
            'failure_reasons' => $attempts->where('successful', false)
                ->pluck('failure_reason')
                ->filter()
                ->countBy()
                ->toArray(),
            'hourly_distribution' => $attempts->where('successful', true)
                ->pluck('login_hour')
                ->filter()
                ->countBy()
                ->toArray(),
            'country_distribution' => $attempts->where('successful', true)
                ->pluck('country_code')
                ->filter()
                ->countBy()
                ->toArray(),
        ];
    }

    // ========================================
    // Bot Detection
    // ========================================

    /**
     * Detect bot behavior from login request
     *
     * @return array{is_bot_suspected: bool, signals: string[], bot_score: int}
     */
    public function detectBotBehavior(Request $request, string $identifier): array
    {
        $signals = [];
        $score = 0;

        // 1. Known bot user-agent patterns
        $ua = $request->userAgent() ?? '';
        if (preg_match('/bot|crawler|spider|scraper|headless|phantom|selenium|puppeteer/i', $ua)) {
            $signals[] = 'known_bot_ua';
            $score += 40;
        }

        // 2. Missing typical browser headers
        if (empty($request->header('Accept-Language'))) {
            $signals[] = 'missing_accept_language';
            $score += 15;
        }

        // 3. Rapid-fire attempts (>5 in 60 seconds from same IP)
        $recentFromIp = MemberLoginAttempt::where('ip_address', $request->ip())
            ->where('attempted_at', '>=', Carbon::now()->subSeconds(60))
            ->count();
        if ($recentFromIp > 5) {
            $signals[] = 'rapid_fire_attempts';
            $score += 30;
        }

        // 4. Credential stuffing pattern (multiple identifiers from same IP)
        $uniqueIdentifiers = MemberLoginAttempt::where('ip_address', $request->ip())
            ->where('attempted_at', '>=', Carbon::now()->subMinutes(5))
            ->distinct()
            ->count('identifier');
        if ($uniqueIdentifiers > 3) {
            $signals[] = 'credential_stuffing_pattern';
            $score += 40;
        }

        return [
            'is_bot_suspected' => $score >= 30,
            'signals' => $signals,
            'bot_score' => min($score, 100),
        ];
    }
}
