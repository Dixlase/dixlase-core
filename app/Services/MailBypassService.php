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

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * メール送信緊急バイパスサービス（ブレークグラス）
 * 
 * SMTPサーバー障害時に、メール依存機能を一時的にバイパスする
 * 
 * 対象機能:
 * - 2FA（メール認証をスキップ）
 * - パスワードリセット（メール送信をスキップして直接リセット可能に）
 * 
 * 特徴:
 * - 時間制限付き（最大120分）
 * - スコープ指定可能（2fa, password_reset, all）
 * - 監査ログに必ず記録
 * - 自動で期限切れ
 */
class MailBypassService
{
    /**
     * キャッシュキー
     */
    private const CACHE_KEY_BYPASS = 'mail_bypass';
    private const CACHE_KEY_BYPASS_DATA = 'mail_bypass_data';

    /**
     * 最大バイパス時間（分）
     */
    public const MAX_BYPASS_MINUTES = 120;

    /**
     * バイパスを有効化
     */
    public static function enable(int $minutes, string $scope, string $reason): bool
    {
        // 最大時間を制限
        $minutes = min($minutes, self::MAX_BYPASS_MINUTES);
        
        $expiresAt = now()->addMinutes($minutes);
        
        $data = [
            'active' => true,
            'scope' => $scope,
            'reason' => $reason,
            'enabled_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'minutes' => $minutes,
        ];

        try {
            // キャッシュに保存（期限付き）
            Cache::put(self::CACHE_KEY_BYPASS, true, $expiresAt);
            Cache::put(self::CACHE_KEY_BYPASS_DATA, $data, $expiresAt);

            Log::channel('admin_activity')->warning('Mail bypass enabled', [
                'scope' => $scope,
                'reason' => $reason,
                'minutes' => $minutes,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('Failed to enable mail bypass', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * バイパスを無効化
     */
    public static function disable(): void
    {
        Cache::forget(self::CACHE_KEY_BYPASS);
        Cache::forget(self::CACHE_KEY_BYPASS_DATA);

        Log::channel('admin_activity')->info('Mail bypass disabled');
    }

    /**
     * バイパスがアクティブかチェック
     */
    public static function isActive(?string $scope = null): bool
    {
        if (!Cache::get(self::CACHE_KEY_BYPASS, false)) {
            return false;
        }

        // スコープが指定されている場合、スコープもチェック
        if ($scope !== null) {
            $data = Cache::get(self::CACHE_KEY_BYPASS_DATA, []);
            $bypassScope = $data['scope'] ?? '2fa';
            
            // 'all' スコープは全てにマッチ
            if ($bypassScope === 'all') {
                return true;
            }
            
            return $bypassScope === $scope;
        }

        return true;
    }

    /**
     * 2FAメール認証をスキップすべきかチェック
     */
    public static function shouldSkip2faMail(): bool
    {
        return self::isActive('2fa');
    }

    /**
     * パスワードリセットメールをスキップすべきかチェック
     */
    public static function shouldSkipPasswordResetMail(): bool
    {
        return self::isActive('password_reset');
    }

    /**
     * バイパスの状態を取得
     */
    public static function getStatus(): array
    {
        $isActive = Cache::get(self::CACHE_KEY_BYPASS, false);
        $data = Cache::get(self::CACHE_KEY_BYPASS_DATA, []);

        if (!$isActive || empty($data)) {
            return [
                'active' => false,
                'scope' => null,
                'reason' => null,
                'enabled_at' => null,
                'expires_at' => null,
                'remaining_minutes' => 0,
            ];
        }

        $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
        $remainingMinutes = max(0, now()->diffInMinutes($expiresAt, false));

        return [
            'active' => true,
            'scope' => $data['scope'],
            'reason' => $data['reason'],
            'enabled_at' => $data['enabled_at'],
            'expires_at' => $data['expires_at'],
            'remaining_minutes' => $remainingMinutes,
        ];
    }
}
