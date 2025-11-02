<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member2faToken extends Model
{
    use HasFactory;

    protected $table = 'members_2fa_tokens';

    protected $fillable = [
        'member_id',
        'code',
        'expires_at',
    ];
    protected $dates = ['expires_at'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
