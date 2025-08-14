<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminLoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * 指定した識別子（メールアドレス等）の失敗した試行回数を取得
     *
     * @param string $identifier
     * @param int $timeWindowMinutes
     * @return int
     */
    public static function getFailedAttemptsCount(string $identifier, int $timeWindowMinutes): int
    {
        $cutoffTime = Carbon::now()->subMinutes($timeWindowMinutes);

        return static::where('identifier', $identifier)
            ->where('successful', false)
            ->where('attempted_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 指定したIPアドレスの失敗した試行回数を取得
     *
     * @param string $ipAddress
     * @param int $timeWindowMinutes
     * @return int
     */
    public static function getFailedAttemptsCountByIp(string $ipAddress, int $timeWindowMinutes): int
    {
        $cutoffTime = Carbon::now()->subMinutes($timeWindowMinutes);

        return static::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', $cutoffTime)
            ->count();
    }

    /**
     * 最後の失敗した試行時刻を取得
     *
     * @param string $identifier
     * @return Carbon|null
     */
    public static function getLastFailedAttempt(string $identifier): ?Carbon
    {
        $attempt = static::where('identifier', $identifier)
            ->where('successful', false)
            ->orderBy('attempted_at', 'desc')
            ->first();

        return $attempt ? $attempt->attempted_at : null;
    }

    /**
     * ログイン試行を記録
     *
     * @param string $identifier
     * @param string $ipAddress
     * @param string|null $userAgent
     * @param bool $successful
     * @return static
     */
    public static function recordAttempt(
        string $identifier,
        string $ipAddress,
        ?string $userAgent = null,
        bool $successful = false
    ): static {
        return static::create([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => Carbon::now(),
        ]);
    }

    /**
     * 成功したログイン後、過去の失敗記録をクリア
     *
     * @param string $identifier
     * @return void
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        static::where('identifier', $identifier)
            ->where('successful', false)
            ->delete();
    }

    /**
     * 古いログイン試行記録を削除（クリーンアップ用）
     *
     * @param int $daysOld
     * @return int
     */
    public static function cleanupOldAttempts(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return static::where('attempted_at', '<', $cutoffDate)->delete();
    }
}
