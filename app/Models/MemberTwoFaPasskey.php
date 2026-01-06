<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laragear\WebAuthn\Models\WebAuthnCredential as BaseWebAuthnCredential;

class MemberTwoFaPasskey extends BaseWebAuthnCredential
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'members_two_fa_passkeys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'member_id',
        'alias',
        'counter',
        'rp_id',
        'origin',
        'transports',
        'aaguid',
        'public_key',
        'attestation_format',
        'certificates',
        'disabled_at',
        'name',
    ];

    /**
     * Get the name of the user ID column.
     *
     * @return string
     */
    public function getUserIdColumn(): string
    {
        return 'member_id';
    }

    /**
     * Get the member that owns the credential.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the authenticatable entity (member).
     */
    public function user(): BelongsTo
    {
        return $this->member();
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
