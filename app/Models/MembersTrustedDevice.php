<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembersTrustedDevice extends Model
{
    use HasFactory;
    
    protected $table = 'members_trusted_devices';

    protected $fillable = [
        'member_id',
        'device_name',
        'token',
        'ip_address',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
