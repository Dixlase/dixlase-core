<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member2faRecoveryCode extends Model
{
    use HasFactory;

    protected $table = 'members_two_fa_recovery_codes';

    protected $fillable = [
        'member_id',
        'code',
        'used_at',
        'disabled',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'disabled' => 'boolean',
    ];

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 有効な回復コードかどうか
     */
    public function isValid(): bool
    {
        return !$this->disabled && is_null($this->used_at);
    }

    /**
     * 使用済みとしてマーク
     */
    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    /**
     * 無効化
     */
    public function disable(): void
    {
        $this->disabled = true;
        $this->save();
    }
}
