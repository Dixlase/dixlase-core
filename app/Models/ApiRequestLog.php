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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * APIリクエストログモデル
 * 
 * β版でのAPIレートリミット機能の基盤として使用
 */
class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';

    protected $fillable = [
        'api_key_id',
        'api_key_prefix',
        'method',
        'endpoint',
        'path',
        'query_params',
        'request_size',
        'response_code',
        'response_size',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'rate_limited',
        'current_rate',
        'error_code',
        'error_message',
        'requested_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'rate_limited' => 'boolean',
        'request_size' => 'integer',
        'response_size' => 'integer',
        'response_time_ms' => 'integer',
        'response_code' => 'integer',
        'current_rate' => 'integer',
        'requested_at' => 'datetime',
    ];

    // ========================================
    // リレーション
    // ========================================

    /**
     * APIキーとのリレーション
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    // ========================================
    // レートリミット用メソッド（β版で実装予定）
    // ========================================

    /**
     * 指定APIキーの直近のリクエスト数を取得
     */
    public static function getRequestCount(int $apiKeyId, int $windowSeconds = 60): int
    {
        $cutoffTime = Carbon::now()->subSeconds($windowSeconds);

        return static::where('api_key_id', $apiKeyId)
            ->where('requested_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 指定IPアドレスの直近のリクエスト数を取得
     */
    public static function getRequestCountByIp(string $ipAddress, int $windowSeconds = 60): int
    {
        $cutoffTime = Carbon::now()->subSeconds($windowSeconds);

        return static::where('ip_address', $ipAddress)
            ->where('requested_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 指定エンドポイントの直近のリクエスト数を取得
     */
    public static function getRequestCountByEndpoint(
        string $endpoint,
        ?int $apiKeyId = null,
        int $windowSeconds = 60
    ): int {
        $cutoffTime = Carbon::now()->subSeconds($windowSeconds);

        $query = static::where('endpoint', $endpoint)
            ->where('requested_at', '>=', $cutoffTime);

        if ($apiKeyId) {
            $query->where('api_key_id', $apiKeyId);
        }

        return $query->count();
    }

    /**
     * レートリミット超過かどうかを判定
     */
    public static function isRateLimited(int $apiKeyId, int $limit, int $windowSeconds = 60): bool
    {
        return static::getRequestCount($apiKeyId, $windowSeconds) >= $limit;
    }

    /**
     * IP別レートリミット超過かどうかを判定
     */
    public static function isIpRateLimited(string $ipAddress, int $limit, int $windowSeconds = 60): bool
    {
        return static::getRequestCountByIp($ipAddress, $windowSeconds) >= $limit;
    }

    /**
     * APIリクエストを記録
     */
    public static function logRequest(array $data): static
    {
        $data['requested_at'] = $data['requested_at'] ?? Carbon::now();
        return static::create($data);
    }

    /**
     * 古いログを削除（クリーンアップ用）
     */
    public static function cleanupOldLogs(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        return static::where('requested_at', '<', $cutoffDate)->delete();
    }

    // ========================================
    // 統計・分析用メソッド
    // ========================================

    /**
     * APIキー別の使用統計を取得
     */
    public static function getUsageStats(int $apiKeyId, int $days = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $logs = static::where('api_key_id', $apiKeyId)
            ->where('requested_at', '>=', $cutoffDate)
            ->get();

        return [
            'total_requests' => $logs->count(),
            'successful_requests' => $logs->whereBetween('response_code', [200, 299])->count(),
            'error_requests' => $logs->where('response_code', '>=', 400)->count(),
            'rate_limited_requests' => $logs->where('rate_limited', true)->count(),
            'average_response_time_ms' => $logs->avg('response_time_ms'),
            'total_request_size' => $logs->sum('request_size'),
            'total_response_size' => $logs->sum('response_size'),
            'unique_ips' => $logs->pluck('ip_address')->unique()->count(),
            'endpoints' => $logs->pluck('endpoint')->countBy()->toArray(),
            'methods' => $logs->pluck('method')->countBy()->toArray(),
            'response_codes' => $logs->pluck('response_code')->countBy()->toArray(),
        ];
    }

    /**
     * エンドポイント別の使用統計を取得
     */
    public static function getEndpointStats(int $days = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $logs = static::where('requested_at', '>=', $cutoffDate)->get();

        $endpoints = [];
        foreach ($logs->groupBy('endpoint') as $endpoint => $endpointLogs) {
            $endpoints[$endpoint] = [
                'total_requests' => $endpointLogs->count(),
                'average_response_time_ms' => $endpointLogs->avg('response_time_ms'),
                'error_rate' => $endpointLogs->count() > 0
                    ? round($endpointLogs->where('response_code', '>=', 400)->count() / $endpointLogs->count() * 100, 2)
                    : 0,
            ];
        }

        return $endpoints;
    }

    /**
     * 時間別のリクエスト分布を取得
     */
    public static function getHourlyDistribution(int $days = 1): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $logs = static::where('requested_at', '>=', $cutoffDate)->get();

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = 0;
        }

        foreach ($logs as $log) {
            $hour = $log->requested_at->hour;
            $hours[$hour]++;
        }

        return $hours;
    }

    /**
     * エラー分析を取得
     */
    public static function getErrorAnalysis(int $days = 7): array
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $logs = static::where('requested_at', '>=', $cutoffDate)
            ->where('response_code', '>=', 400)
            ->get();

        return [
            'total_errors' => $logs->count(),
            'by_code' => $logs->pluck('response_code')->countBy()->toArray(),
            'by_endpoint' => $logs->pluck('endpoint')->countBy()->toArray(),
            'by_error_code' => $logs->pluck('error_code')->filter()->countBy()->toArray(),
            'rate_limited_count' => $logs->where('rate_limited', true)->count(),
        ];
    }

    // ========================================
    // スコープ
    // ========================================

    /**
     * 成功したリクエストのみ
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_code', [200, 299]);
    }

    /**
     * エラーリクエストのみ
     */
    public function scopeErrors($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * レートリミット超過のみ
     */
    public function scopeRateLimited($query)
    {
        return $query->where('rate_limited', true);
    }

    /**
     * 特定期間のリクエスト
     */
    public function scopeInPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('requested_at', [$start, $end]);
    }

    /**
     * 直近のリクエスト
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('requested_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * 特定エンドポイントのリクエスト
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * 特定メソッドのリクエスト
     */
    public function scopeWithMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * 遅いリクエスト
     */
    public function scopeSlow($query, int $minMs = 1000)
    {
        return $query->where('response_time_ms', '>=', $minMs);
    }
}
