<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Services;

use App\Models\MemberLoginAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン行動分析サービス
 *
 * β版での行動分析機能の基盤として使用
 * ログイン時のコンテキスト収集と異常検知を担当
 */
class LoginBehaviorService
{
    /**
     * リクエストからログインコンテキストを収集
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
     * デバイスフィンガープリントを生成
     *
     * UserAgent + Accept-Language + Accept-Encoding のハッシュ
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
     * IPアドレスから国コードを取得
     *
     * β版でGeoIPライブラリを統合予定
     * 現在はnullを返す（プレースホルダー）
     */
    public function getCountryCode(Request $request): ?string
    {
        // TODO: β版でGeoIP2/MaxMindを統合
        // 現在はプレースホルダーとしてnullを返す
        return null;
    }

    /**
     * ログイン試行を記録（行動分析データ付き）
     */
    public function recordLoginAttempt(
        string $identifier,
        Request $request,
        bool $successful,
        array $additionalData = []
    ): MemberLoginAttempt {
        $context = $this->collectLoginContext($request);

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
     * ログイン成功を記録
     */
    public function recordSuccessfulLogin(
        string $identifier,
        Request $request,
        array $additionalData = []
    ): MemberLoginAttempt {
        return $this->recordLoginAttempt($identifier, $request, true, $additionalData);
    }

    /**
     * ログイン失敗を記録
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
     * 現在のログインが異常かどうかを判定
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
     * ユーザーの行動プロファイルを取得
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
     * リスクスコアを計算（β版で拡張予定）
     *
     * @return int 0-100のリスクスコア
     */
    public function calculateRiskScore(string $identifier, Request $request): int
    {
        $anomaly = $this->detectAnomaly($identifier, $request);
        $riskScore = $anomaly['risk_score'];

        // 追加のリスク要因（β版で拡張予定）
        // - 連続失敗回数
        // - 短時間での複数IP
        // - VPN/Tor検知
        // - ブルートフォース検知

        // 連続失敗回数によるリスク加算
        $recentFailures = MemberLoginAttempt::getFailedAttemptsCount($identifier, 60);
        if ($recentFailures >= 3) {
            $riskScore += min($recentFailures * 5, 30);
        }

        return min($riskScore, 100);
    }

    /**
     * 高リスクログインの一覧を取得
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
     * 特定ユーザーの高リスクログイン履歴を取得
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
     * ログイン行動の要約レポートを生成
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
}
