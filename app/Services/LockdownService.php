<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Emergency lockdown service
 *
 * Provides immediate system protection during security incidents
 */
class LockdownService
{
    /**
     * Cache key
     */
    protected const CACHE_KEY = 'lockdown_status';

    protected const CACHE_TTL = 60; // 1 minute

    /**
     * Activate lockdown
     *
     * @param  string  $type  Lockdown type
     * @param  string  $reason  Reason
     * @param  int|null  $triggeredBy  Activator ID (null=system automatic)
     * @param  int|null  $autoReleaseMinutes  Minutes until automatic release
     * @param  array|null  $allowedIps  Allowed IP addresses
     * @param  array|null  $allowedMembers  Allowed member IDs
     */
    public static function activate(
        string $type = LockdownStatus::TYPE_FULL,
        string $reason = '',
        ?int $triggeredBy = null,
        ?int $autoReleaseMinutes = null,
        ?array $allowedIps = null,
        ?array $allowedMembers = null
    ): LockdownStatus {
        // Release existing active lockdown
        self::deactivateAll();

        // Create new lockdown
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

        // Record history
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

        // Record audit log
        AuditLog::logSecurity('lockdown_activated', [
            'severity' => AuditLog::SEVERITY_ALERT,
            'context' => [
                'type' => $type,
                'reason' => $reason,
                'auto_release_minutes' => $autoReleaseMinutes,
            ],
        ]);

        // Clear cache
        self::clearCache();

        Log::channel('admin_error')->alert('Lockdown activated', [
            'type' => $type,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
        ]);

        return $lockdown;
    }

    /**
     * Release lockdown
     *
     * @param  int|null  $releasedBy  Releaser ID
     * @param  string|null  $reason  Release reason
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

        // Record history
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

        // Record audit log
        AuditLog::logSecurity('lockdown_deactivated', [
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'type' => $lockdown->type,
                'reason' => $reason,
                'duration_minutes' => $lockdown->triggered_at->diffInMinutes(now()),
            ],
        ]);

        // Clear cache
        self::clearCache();

        Log::channel('admin_activity')->info('Lockdown deactivated', [
            'type' => $lockdown->type,
            'released_by' => $releasedBy,
        ]);

        return true;
    }

    /**
     * Release all lockdowns
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
     * Check and execute auto-release
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

        // Record history
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

        // Record audit log
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
     * Get lockdown status (with cache)
     */
    public static function getStatus(): ?LockdownStatus
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return LockdownStatus::getActive();
        });
    }

    /**
     * Whether in lockdown
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

        // Full lockdown affects everything
        if ($status->type === LockdownStatus::TYPE_FULL) {
            return true;
        }

        return $status->type === $type;
    }

    /**
     * Check if access is allowed
     *
     * @param  string|null  $ip  IP address
     * @param  Member|null  $member  member
     * @param  string|null  $type  Lockdown type to check
     */
    public static function isAccessAllowed(?string $ip = null, ?Member $member = null, ?string $type = null): bool
    {
        $status = self::getStatus();
        if (! $status) {
            return true; // No lockdown
        }

        // If type is specified and that type is not locked
        if ($type !== null && $status->type !== LockdownStatus::TYPE_FULL && $status->type !== $type) {
            return true;
        }

        // SUPER_ADMIN always has access
        if ($member && $member->role === MemberRole::SUPER_ADMIN) {
            return true;
        }

        // Check if IP is allowed
        if ($ip && $status->isIpAllowed($ip)) {
            return true;
        }

        // Check if member is allowed
        if ($member && $status->isMemberAllowed($member->id)) {
            return true;
        }

        return false;
    }

    /**
     * Extend lockdown
     *
     * @param  int  $additionalMinutes  Minutes to add
     * @param  int|null  $performedBy  Executor ID
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

        // Record history
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
     * Update allow list
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

        // Record history
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
     * Get statistics
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
     * Clear cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
