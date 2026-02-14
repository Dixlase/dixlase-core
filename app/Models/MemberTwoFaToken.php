<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberTwoFaToken extends Model
{
    use HasFactory;

    protected $table = 'members_two_fa_tokens';

    protected $fillable = [
        'member_id',
        'code',
        'expires_at',
    ];
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
