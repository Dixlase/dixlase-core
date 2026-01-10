<?php

namespace App\Services\TwoFa;

use App\Contracts\TwoFaInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwoFaAttemptService
{
    /**
     * 2FA試行を記録
     */
    public function recordAttempt(TwoFaInterface $user, string $attemptType, bool $success): void
    {
        $user->twoFaAttempts()->create([
            'attempt_type' => $attemptType,
            'success' => $success,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('[2FA Attempt] Recorded', [
            'user_id' => $user->getId(),
            'attempt_type' => $attemptType,
            'success' => $success,
        ]);
    }

    /**
     * ロックアウト状態かチェック
     */
    public function isLockedOut(TwoFaInterface $user): bool
    {
        $lockoutDuration = $this->getLockoutDuration();
        $lastLockout = $this->getLastLockoutTime($user);

        if (!$lastLockout) {
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
        if (!$this->isLockedOut($user)) {
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
            ->where('success', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->count();

        Log::info('[2FA Attempt] Check max attempts', [
            'user_id' => $user->getId(),
            'failed_attempts' => $failedAttempts,
            'max_attempts' => $maxAttempts,
        ]);

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
            ->where('success', false)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->count();

        return max(0, $maxAttempts - $failedAttempts);
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
            ->where('success', false)
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
        return (int) \App\Models\MemberSetting::getValue('two_fa_max_attempts', 5);
    }

    /**
     * 試行制限の時間枠（分）を取得
     */
    protected function getAttemptWindow(): int
    {
        return (int) \App\Models\MemberSetting::getValue('two_fa_attempt_window', 15);
    }

    /**
     * ロックアウト時間（分）を取得
     */
    protected function getLockoutDuration(): int
    {
        return (int) \App\Models\MemberSetting::getValue('two_fa_lockout_duration', 30);
    }

    /**
     * ロックアウト通知が有効かチェック
     */
    public function isLockoutNotificationEnabled(): bool
    {
        return (bool) \App\Models\MemberSetting::getValue('two_fa_lockout_notification_enabled', true);
    }
}
