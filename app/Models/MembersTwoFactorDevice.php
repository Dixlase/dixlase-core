<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembersTwoFactorDevice extends Model
{
    use HasFactory;
    protected $table = 'members_two_factor_devices';

    protected $fillable = [
        'member_id',
        'token',
        'ip_address',
        'user_agent',
        'approved',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * チャレンジが有効かどうか
     */
    public function isValid(): bool
    {
        return !$this->approved && $this->expires_at->isFuture();
    }

    /**
     * チャレンジが期限切れかどうか
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
