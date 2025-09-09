<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    protected $table = 'members_trusted_devices';
    
    protected $fillable = [
        'member_id',
        'device_name',
        'token',
        'ip_address',
        'user_agent',
        'last_used_at',
    ];

    protected $dates = [
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}