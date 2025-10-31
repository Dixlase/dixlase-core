<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Member2faAttempt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwoFactorAttemptService
{
    /**
     * 2FA試行を記録
     */
    public function recordAttempt(Member $member, string $attemptType, bool $success): void
    {
        Member2faAttempt::record($member->id, $attemptType, $success);

        Log::info('[2FA Attempt] Recorded', [
            'member_id' => $member->id,
            'attempt_type' => $attemptType,
            'success' => $success,
        ]);
    }

    /**
     * ロックアウト状態かチェック
     */
    public function isLockedOut(Member $member): bool
    {
        $lockoutDuration = $this->getLockoutDuration();
        $lastLockout = $this->getLastLockoutTime($member);

        if (!$lastLockout) {
            return false;
        }

        $unlockAt = $lastLockout->addMinutes($lockoutDuration);
        return Carbon::now()->lessThan($unlockAt);
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     */
    public function getRemainingLockoutTime(Member $member): ?int
    {
        if (!$this->isLockedOut($member)) {
            return null;
        }

        $lockoutDuration = $this->getLockoutDuration();
        $lastLockout = $this->getLastLockoutTime($member);
        $unlockAt = $lastLockout->addMinutes($lockoutDuration);

        return Carbon::now()->diffInMinutes($unlockAt, false);
    }

    /**
     * 試行回数制限に達しているかチェック
     */
    public function hasReachedMaxAttempts(Member $member): bool
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();

        $failedAttempts = Member2faAttempt::getFailedAttemptsCount($member->id, $timeWindow);

        Log::info('[2FA Attempt] Check max attempts', [
            'member_id' => $member->id,
            'failed_attempts' => $failedAttempts,
            'max_attempts' => $maxAttempts,
        ]);

        return $failedAttempts >= $maxAttempts;
    }

    /**
     * 残りの試行可能回数を取得
     */
    public function getRemainingAttempts(Member $member): int
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();
        $failedAttempts = Member2faAttempt::getFailedAttemptsCount($member->id, $timeWindow);

        return max(0, $maxAttempts - $failedAttempts);
    }

    /**
     * 最後のロックアウト時刻を取得
     */
    protected function getLastLockoutTime(Member $member): ?Carbon
    {
        $maxAttempts = $this->getMaxAttempts();
        $timeWindow = $this->getAttemptWindow();

        // 時間枠内の失敗試行を取得
        $attempts = Member2faAttempt::where('member_id', $member->id)
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
    public function handleSuccess(Member $member): void
    {
        // 成功を記録（attempt_typeは呼び出し元で指定）
        Log::info('[2FA Attempt] Success - clearing failed attempts', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 最大試行回数を取得
     */
    protected function getMaxAttempts(): int
    {
        return (int) \App\Models\MemberSetting::getValue('2fa_max_attempts', 5);
    }

    /**
     * 試行制限の時間枠（分）を取得
     */
    protected function getAttemptWindow(): int
    {
        return (int) \App\Models\MemberSetting::getValue('2fa_attempt_window', 15);
    }

    /**
     * ロックアウト時間（分）を取得
     */
    protected function getLockoutDuration(): int
    {
        return (int) \App\Models\MemberSetting::getValue('2fa_lockout_duration', 30);
    }

    /**
     * ロックアウト通知が有効かチェック
     */
    public function isLockoutNotificationEnabled(): bool
    {
        return (bool) \App\Models\MemberSetting::getValue('2fa_lockout_notification_enabled', true);
    }
}
