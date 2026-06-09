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

namespace App\Services\TwoFa;

use App\Contracts\TwoFaInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core use only. Do not reference from plugins/themes
 */
class TwoFaAttemptService
{
    protected string $settingModelClass;

    public function __construct(string $settingModelClass = \App\Models\SecuritySetting::class)
    {
        $this->settingModelClass = $settingModelClass;
    }

    /**
     * Record 2FA attempt
     */
    public function recordAttempt(TwoFaInterface $user, string $attemptType, bool $successful): void
    {
        $user->twoFaAttempts()->create([
            'attempt_type' => $attemptType,
            'successful' => $successful,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        Log::info('[2FA Attempt] Recorded', [
            'user_id' => $user->getId(),
            'attempt_type' => $attemptType,
            'successful' => $successful,
        ]);
    }

    /**
     * Check if locked out
     */
    public function isLockedOut(TwoFaInterface $user): bool
    {
        $lockoutDuration = $this->getLockoutDuration();
        $lastLockout = $this->getLastLockoutTime($user);

        if (! $lastLockout) {
            return false;
        }

        $unlockAt = $lastLockout->addMinutes($lockoutDuration);

        return Carbon::now()->lessThan($unlockAt);
    }

    /**
     * Get remaining time until lockout release (minutes)
     */
    public function getRemainingLockoutTime(TwoFaInterface $user): ?int
    {
        if (! $this->isLockedOut($user)) {
            return null;
        }

        $lockoutDuration = $this->getLockoutDuration();
        $lastLockout = $this->getLastLockoutTime($user);
        $unlockAt = $lastLockout->addMinutes($lockoutDuration);

        return Carbon::now()->diffInMinutes($unlockAt, false);
    }

    /**
     * Check if attempt limit has been reached
     */
    public function hasReachedMaxAttempts(TwoFaInterface $user): bool
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();

        $failedAttempts = $user->twoFaAttempts()
            ->where('successful', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->count();

        return $failedAttempts >= $maxAttempts;
    }

    /**
     * Get remaining number of attempts
     */
    public function getRemainingAttempts(TwoFaInterface $user): int
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();
        $failedAttempts = $user->twoFaAttempts()
            ->where('successful', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->count();

        return $maxAttempts - $failedAttempts;
    }

    /**
     * Get last lockout time
     */
    protected function getLastLockoutTime(TwoFaInterface $user): ?Carbon
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();

        // Get failed attempts within time window
        $attempts = $user->twoFaAttempts()
            ->where('successful', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->orderBy('created_at', 'desc')
            ->take($maxAttempts)
            ->get();

        // Return last failure time if maximum attempts reached
        if ($attempts->count() >= $maxAttempts) {
            return $attempts->first()->created_at;
        }

        return null;
    }

    /**
     * Process on success (clear failure records)
     */
    public function handleSuccess(TwoFaInterface $user): void
    {
        // Record success (attempt_type specified by caller)
        Log::info('[2FA Attempt] Success - clearing failed attempts', [
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Get maximum number of attempts
     */
    protected function getMaxAttempts(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_max_attempts', 5);
    }

    /**
     * Get time window for attempt limit (minutes)
     */
    protected function getAttemptWindow(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_attempt_window', 15);
    }

    /**
     * Get lockout duration (minutes)
     */
    protected function getLockoutDuration(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_lockout_duration', 30);
    }

    /**
     * Check if lockout notification is enabled
     */
    public function isLockoutNotificationEnabled(): bool
    {
        return (bool) $this->settingModelClass::getValue('two_fa_lockout_notification_enabled', true);
    }
}
