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

namespace App\Services\TwoFa;

use App\Contracts\TwoFaInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class TwoFaAttemptService
{
    protected string $settingModelClass;

    public function __construct(string $settingModelClass = \App\Models\SecuritySetting::class)
    {
        $this->settingModelClass = $settingModelClass;
    }

    /**
     * 2FA試行を記録
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
     * ロックアウト状態かチェック
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
     * ロックアウト解除までの残り時間（分）を取得
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
     * 試行回数制限に達しているかチェック
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
     * 残りの試行可能回数を取得
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
     * 最後のロックアウト時刻を取得
     */
    protected function getLastLockoutTime(TwoFaInterface $user): ?Carbon
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();

        // 時間枠内の失敗試行を取得
        $attempts = $user->twoFaAttempts()
            ->where('successful', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->orderBy('created_at', 'desc')
            ->take($maxAttempts)
            ->get();

        // 最大試行回数に達している場合、最後の失敗時刻を返す
        if ($attempts->count() >= $maxAttempts) {
            return $attempts->first()->created_at;
        }

        return null;
    }

    /**
     * 成功時の処理（失敗記録をクリア）
     */
    public function handleSuccess(TwoFaInterface $user): void
    {
        // 成功を記録（attempt_typeは呼び出し元で指定）
        Log::info('[2FA Attempt] Success - clearing failed attempts', [
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * 最大試行回数を取得
     */
    protected function getMaxAttempts(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_max_attempts', 5);
    }

    /**
     * 試行制限の時間枠（分）を取得
     */
    protected function getAttemptWindow(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_attempt_window', 15);
    }

    /**
     * ロックアウト時間（分）を取得
     */
    protected function getLockoutDuration(): int
    {
        return (int) $this->settingModelClass::getValue('two_fa_lockout_duration', 30);
    }

    /**
     * ロックアウト通知が有効かチェック
     */
    public function isLockoutNotificationEnabled(): bool
    {
        return (bool) $this->settingModelClass::getValue('two_fa_lockout_notification_enabled', true);
    }
}
