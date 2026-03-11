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

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * APIレートリミットサービス
 *
 * β版でのAPIレートリミット機能の基盤として使用
 */
class ApiRateLimitService
{
    /**
     * デフォルトのレートリミット（1分あたり）
     */
    protected int $defaultRateLimit = 60;

    /**
     * デフォルトのIP別レートリミット（1分あたり）
     */
    protected int $defaultIpRateLimit = 100;

    /**
     * レートリミットウィンドウ（秒）
     */
    protected int $windowSeconds = 60;

    /**
     * APIキーのレートリミットをチェック
     */
    public function checkRateLimit(ApiKey $apiKey): array
    {
        $limit = $apiKey->rate_limit ?? $this->defaultRateLimit;
        $currentCount = ApiRequestLog::getRequestCount($apiKey->id, $this->windowSeconds);
        $remaining = max(0, $limit - $currentCount);
        $isLimited = $currentCount >= $limit;

        return [
            'is_limited' => $isLimited,
            'limit' => $limit,
            'remaining' => $remaining,
            'current' => $currentCount,
            'reset_at' => Carbon::now()->addSeconds($this->windowSeconds),
            'window_seconds' => $this->windowSeconds,
        ];
    }

    /**
     * IP別レートリミットをチェック
     */
    public function checkIpRateLimit(string $ipAddress, ?int $limit = null): array
    {
        $limit = $limit ?? $this->defaultIpRateLimit;
        $currentCount = ApiRequestLog::getRequestCountByIp($ipAddress, $this->windowSeconds);
        $remaining = max(0, $limit - $currentCount);
        $isLimited = $currentCount >= $limit;

        return [
            'is_limited' => $isLimited,
            'limit' => $limit,
            'remaining' => $remaining,
            'current' => $currentCount,
            'reset_at' => Carbon::now()->addSeconds($this->windowSeconds),
            'window_seconds' => $this->windowSeconds,
        ];
    }

    /**
     * エンドポイント別レートリミットをチェック
     */
    public function checkEndpointRateLimit(
        string $endpoint,
        ?int $apiKeyId = null,
        ?int $limit = null
    ): array {
        $limit = $limit ?? $this->defaultRateLimit;
        $currentCount = ApiRequestLog::getRequestCountByEndpoint($endpoint, $apiKeyId, $this->windowSeconds);
        $remaining = max(0, $limit - $currentCount);
        $isLimited = $currentCount >= $limit;

        return [
            'is_limited' => $isLimited,
            'limit' => $limit,
            'remaining' => $remaining,
            'current' => $currentCount,
            'reset_at' => Carbon::now()->addSeconds($this->windowSeconds),
        ];
    }

    /**
     * APIリクエストを記録
     */
    public function logRequest(
        Request $request,
        ?ApiKey $apiKey = null,
        int $responseCode = 200,
        ?int $responseTimeMs = null,
        ?int $responseSize = null,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): ApiRequestLog {
        $rateLimitInfo = $apiKey
            ? $this->checkRateLimit($apiKey)
            : $this->checkIpRateLimit($request->ip());

        return ApiRequestLog::logRequest([
            'api_key_id' => $apiKey?->id,
            'api_key_prefix' => $apiKey?->key_prefix,
            'method' => $request->method(),
            'endpoint' => $this->extractEndpoint($request),
            'path' => $request->path(),
            'query_params' => $request->query() ?: null,
            'request_size' => strlen($request->getContent()),
            'response_code' => $responseCode,
            'response_size' => $responseSize,
            'response_time_ms' => $responseTimeMs,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'rate_limited' => $rateLimitInfo['is_limited'],
            'current_rate' => $rateLimitInfo['current'],
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * エンドポイントを抽出（パラメータを正規化）
     */
    protected function extractEndpoint(Request $request): string
    {
        $path = $request->path();

        // 数値IDを{id}に置換
        $endpoint = preg_replace('/\/\d+/', '/{id}', $path);

        // UUIDを{uuid}に置換
        $endpoint = preg_replace(
            '/\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            '/{uuid}',
            $endpoint
        );

        return '/'.ltrim($endpoint, '/');
    }

    /**
     * レートリミットヘッダーを生成
     */
    public function getRateLimitHeaders(array $rateLimitInfo): array
    {
        return [
            'X-RateLimit-Limit' => $rateLimitInfo['limit'],
            'X-RateLimit-Remaining' => $rateLimitInfo['remaining'],
            'X-RateLimit-Reset' => $rateLimitInfo['reset_at']->timestamp,
        ];
    }

    /**
     * レートリミット超過時のレスポンスデータを生成
     */
    public function getRateLimitExceededResponse(array $rateLimitInfo): array
    {
        return [
            'error' => 'rate_limit_exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $this->windowSeconds,
            'limit' => $rateLimitInfo['limit'],
            'current' => $rateLimitInfo['current'],
        ];
    }

    /**
     * APIキーの使用統計を取得
     */
    public function getApiKeyStats(int $apiKeyId, int $days = 7): array
    {
        return ApiRequestLog::getUsageStats($apiKeyId, $days);
    }

    /**
     * 全体のAPI使用統計を取得
     */
    public function getOverallStats(int $days = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $logs = ApiRequestLog::where('requested_at', '>=', $cutoffDate)->get();

        return [
            'period_days' => $days,
            'total_requests' => $logs->count(),
            'successful_requests' => $logs->whereBetween('response_code', [200, 299])->count(),
            'error_requests' => $logs->where('response_code', '>=', 400)->count(),
            'rate_limited_requests' => $logs->where('rate_limited', true)->count(),
            'average_response_time_ms' => round($logs->avg('response_time_ms'), 2),
            'unique_api_keys' => $logs->pluck('api_key_id')->filter()->unique()->count(),
            'unique_ips' => $logs->pluck('ip_address')->unique()->count(),
            'top_endpoints' => $logs->pluck('endpoint')
                ->countBy()
                ->sortDesc()
                ->take(10)
                ->toArray(),
            'error_rate' => $logs->count() > 0
                ? round($logs->where('response_code', '>=', 400)->count() / $logs->count() * 100, 2)
                : 0,
        ];
    }

    /**
     * レートリミット設定を更新
     */
    public function setDefaultRateLimit(int $limit): self
    {
        $this->defaultRateLimit = $limit;

        return $this;
    }

    /**
     * IP別レートリミット設定を更新
     */
    public function setDefaultIpRateLimit(int $limit): self
    {
        $this->defaultIpRateLimit = $limit;

        return $this;
    }

    /**
     * ウィンドウサイズを更新
     */
    public function setWindowSeconds(int $seconds): self
    {
        $this->windowSeconds = $seconds;

        return $this;
    }

    /**
     * 古いログをクリーンアップ
     */
    public function cleanupOldLogs(int $daysOld = 30): int
    {
        return ApiRequestLog::cleanupOldLogs($daysOld);
    }
}
