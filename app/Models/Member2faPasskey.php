<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member2faPasskey extends Model
{
    use SoftDeletes;

    protected $table = 'members_2fa_passkeys';

    protected $fillable = [
        'member_id',
        'credential_id',
        'public_key',
        'name',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 最終使用日時を更新
     */
    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * デバイス名を更新
     */
    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->save();
    }
}
