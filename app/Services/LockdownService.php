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

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Models\AuditLog;
use App\Models\LockdownHistory;
use App\Models\LockdownStatus;
use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 緊急ロックダウンサービス
 *
 * セキュリティインシデント時の即座のシステム保護を提供
 */
class LockdownService
{
    /**
     * キャッシュキー
     */
    protected const CACHE_KEY = 'lockdown_status';

    protected const CACHE_TTL = 60; // 1分

    /**
     * ロックダウンを発動
     *
     * @param  string  $type  ロックダウンタイプ
     * @param  string  $reason  理由
     * @param  int|null  $triggeredBy  発動者ID（null=システム自動）
     * @param  int|null  $autoReleaseMinutes  自動解除までの分数
     * @param  array|null  $allowedIps  許可するIPアドレス
     * @param  array|null  $allowedMembers  許可するメンバーID
     */
    public static function activate(
        string $type = LockdownStatus::TYPE_FULL,
        string $reason = '',
        ?int $triggeredBy = null,
        ?int $autoReleaseMinutes = null,
        ?array $allowedIps = null,
        ?array $allowedMembers = null
    ): LockdownStatus {
        // 既存のアクティブなロックダウンを解除
        self::deactivateAll();

        // 新しいロックダウンを作成
        $lockdown = LockdownStatus::create([
            'type' => $type,
            'is_active' => true,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
            'triggered_at' => now(),
            'auto_release_at' => $autoReleaseMinutes ? now()->addMinutes($autoReleaseMinutes) : null,
            'allowed_ips' => $allowedIps,
            'allowed_members' => $allowedMembers,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
            ],
        ]);

        // 履歴を記録
        LockdownHistory::record(
            LockdownHistory::ACTION_ACTIVATED,
            $type,
            $reason,
            $triggeredBy,
            request()->ip(),
            [
                'auto_release_minutes' => $autoReleaseMinutes,
                'allowed_ips_count' => $allowedIps ? count($allowedIps) : 0,
                'allowed_members_count' => $allowedMembers ? count($allowedMembers) : 0,
            ]
        );

        // 監査ログを記録
        AuditLog::logSecurity('lockdown_activated', [
            'severity' => AuditLog::SEVERITY_ALERT,
            'context' => [
                'type' => $type,
                'reason' => $reason,
                'auto_release_minutes' => $autoReleaseMinutes,
            ],
        ]);

        // キャッシュをクリア
        self::clearCache();

        Log::channel('admin_error')->alert('Lockdown activated', [
            'type' => $type,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
        ]);

        return $lockdown;
    }

    /**
     * ロックダウンを解除
     *
     * @param  int|null  $releasedBy  解除者ID
     * @param  string|null  $reason  解除理由
     */
    public static function deactivate(?int $releasedBy = null, ?string $reason = null): bool
    {
        $lockdown = LockdownStatus::getActive();
        if (! $lockdown) {
            return false;
        }

        $lockdown->update([
            'is_active' => false,
            'released_by' => $releasedBy,
            'released_at' => now(),
        ]);

        // 履歴を記録
        LockdownHistory::record(
            LockdownHistory::ACTION_DEACTIVATED,
            $lockdown->type,
            $reason,
            $releasedBy,
            request()->ip(),
            [
                'duration_minutes' => $lockdown->triggered_at->diffInMinutes(now()),
            ]
        );

        // 監査ログを記録
        AuditLog::logSecurity('lockdown_deactivated', [
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'type' => $lockdown->type,
                'reason' => $reason,
                'duration_minutes' => $lockdown->triggered_at->diffInMinutes(now()),
            ],
        ]);

        // キャッシュをクリア
        self::clearCache();

        Log::channel('admin_activity')->info('Lockdown deactivated', [
            'type' => $lockdown->type,
            'released_by' => $releasedBy,
        ]);

        return true;
    }

    /**
     * 全てのロックダウンを解除
     */
    public static function deactivateAll(): void
    {
        LockdownStatus::active()->update([
            'is_active' => false,
            'released_at' => now(),
        ]);
        self::clearCache();
    }

    /**
     * 自動解除をチェックして実行
     */
    public static function checkAutoRelease(): bool
    {
        $lockdown = LockdownStatus::getActive();
        if (! $lockdown || ! $lockdown->shouldAutoRelease()) {
            return false;
        }

        $lockdown->update([
            'is_active' => false,
            'released_at' => now(),
        ]);

        // 履歴を記録
        LockdownHistory::record(
            LockdownHistory::ACTION_AUTO_RELEASED,
            $lockdown->type,
            'Auto-released after timeout',
            null,
            null,
            [
                'duration_minutes' => $lockdown->triggered_at->diffInMinutes(now()),
            ]
        );

        // 監査ログを記録
        AuditLog::logSecurity('lockdown_auto_released', [
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'type' => $lockdown->type,
            ],
        ]);

        self::clearCache();

        Log::channel('admin_activity')->info('Lockdown auto-released', [
            'type' => $lockdown->type,
        ]);

        return true;
    }

    /**
     * ロックダウン状態を取得（キャッシュ付き）
     */
    public static function getStatus(): ?LockdownStatus
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return LockdownStatus::getActive();
        });
    }

    /**
     * ロックダウン中かどうか
     */
    public static function isLocked(?string $type = null): bool
    {
        $status = self::getStatus();
        if (! $status) {
            return false;
        }

        if ($type === null) {
            return true;
        }

        // fullロックダウンは全てに影響
        if ($status->type === LockdownStatus::TYPE_FULL) {
            return true;
        }

        return $status->type === $type;
    }

    /**
     * アクセスが許可されているかチェック
     *
     * @param  string|null  $ip  IPアドレス
     * @param  Member|null  $member  メンバー
     * @param  string|null  $type  チェックするロックダウンタイプ
     */
    public static function isAccessAllowed(?string $ip = null, ?Member $member = null, ?string $type = null): bool
    {
        $status = self::getStatus();
        if (! $status) {
            return true; // ロックダウンなし
        }

        // タイプが指定されていて、そのタイプがロックされていない場合
        if ($type !== null && $status->type !== LockdownStatus::TYPE_FULL && $status->type !== $type) {
            return true;
        }

        // SUPER_ADMINは常にアクセス可能
        if ($member && $member->role === MemberRole::SUPER_ADMIN) {
            return true;
        }

        // 許可されたIPかチェック
        if ($ip && $status->isIpAllowed($ip)) {
            return true;
        }

        // 許可されたメンバーかチェック
        if ($member && $status->isMemberAllowed($member->id)) {
            return true;
        }

        return false;
    }

    /**
     * ロックダウンを延長
     *
     * @param  int  $additionalMinutes  追加する分数
     * @param  int|null  $performedBy  実行者ID
     */
    public static function extend(int $additionalMinutes, ?int $performedBy = null): bool
    {
        $lockdown = LockdownStatus::getActive();
        if (! $lockdown) {
            return false;
        }

        $newAutoRelease = $lockdown->auto_release_at
            ? $lockdown->auto_release_at->addMinutes($additionalMinutes)
            : now()->addMinutes($additionalMinutes);

        $lockdown->update([
            'auto_release_at' => $newAutoRelease,
        ]);

        // 履歴を記録
        LockdownHistory::record(
            LockdownHistory::ACTION_EXTENDED,
            $lockdown->type,
            "Extended by {$additionalMinutes} minutes",
            $performedBy,
            request()->ip(),
            [
                'additional_minutes' => $additionalMinutes,
                'new_auto_release_at' => $newAutoRelease->toIso8601String(),
            ]
        );

        self::clearCache();

        return true;
    }

    /**
     * 許可リストを更新
     */
    public static function updateAllowList(
        ?array $allowedIps = null,
        ?array $allowedMembers = null,
        ?int $performedBy = null
    ): bool {
        $lockdown = LockdownStatus::getActive();
        if (! $lockdown) {
            return false;
        }

        $updates = [];
        if ($allowedIps !== null) {
            $updates['allowed_ips'] = $allowedIps;
        }
        if ($allowedMembers !== null) {
            $updates['allowed_members'] = $allowedMembers;
        }

        if (empty($updates)) {
            return false;
        }

        $lockdown->update($updates);

        // 履歴を記録
        LockdownHistory::record(
            LockdownHistory::ACTION_MODIFIED,
            $lockdown->type,
            'Allow list updated',
            $performedBy,
            request()->ip(),
            [
                'allowed_ips_count' => $allowedIps ? count($allowedIps) : null,
                'allowed_members_count' => $allowedMembers ? count($allowedMembers) : null,
            ]
        );

        self::clearCache();

        return true;
    }

    /**
     * 統計情報を取得
     */
    public static function getStats(int $days = 30): array
    {
        $history = LockdownHistory::recent($days)->get();

        return [
            'total_activations' => $history->where('action', LockdownHistory::ACTION_ACTIVATED)->count(),
            'total_deactivations' => $history->where('action', LockdownHistory::ACTION_DEACTIVATED)->count(),
            'auto_releases' => $history->where('action', LockdownHistory::ACTION_AUTO_RELEASED)->count(),
            'by_type' => $history->where('action', LockdownHistory::ACTION_ACTIVATED)
                ->groupBy('type')
                ->map->count()
                ->toArray(),
            'current_status' => self::getStatus()?->toArray(),
        ];
    }

    /**
     * キャッシュをクリア
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
