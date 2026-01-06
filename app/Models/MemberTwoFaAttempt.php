<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTwoFaAttempt extends Model
{
    protected $table = 'members_two_fa_attempts';
    
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'attempt_type',
        'ip_address',
        'user_agent',
        'success',
        'created_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 指定期間内の失敗試行回数を取得
     */
    public static function getFailedAttemptsCount(int $memberId, int $minutes): int
    {
        return self::where('member_id', $memberId)
            ->where('success', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * 指定期間内のIPアドレスの失敗試行回数を取得
     */
    public static function getFailedAttemptsByIpCount(string $ipAddress, int $minutes): int
    {
        return self::where('ip_address', $ipAddress)
            ->where('success', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * 試行記録を作成
     */
    public static function record(int $memberId, string $attemptType, bool $success): void
    {
        self::create([
            'member_id' => $memberId,
            'attempt_type' => $attemptType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'success' => $success,
            'created_at' => now(),
        ]);
    }
}
