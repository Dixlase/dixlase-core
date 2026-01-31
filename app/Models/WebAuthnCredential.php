<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laragear\WebAuthn\Models\WebAuthnCredential as BaseWebAuthnCredential;

class WebAuthnCredential extends BaseWebAuthnCredential
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webauthn_credentials';

    /**
     * モデルの初期化
     */
    protected static function boot()
    {
        parent::boot();

        // 保存前にauthenticatable_idをmember_idから自動設定
        static::creating(function ($model) {
            if (empty($model->authenticatable_id) && !empty($model->member_id)) {
                $model->authenticatable_id = $model->member_id;
            }
            if (empty($model->authenticatable_type)) {
                $model->authenticatable_type = 'App\\Models\\Member';
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'authenticatable_type',
        'authenticatable_id',
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
     * Laragearのuser_idの代わりにmember_idを使用
     *
     * @return string
     */
    public function getUserIdColumn(): string
    {
        return 'member_id';
    }
    
    /**
     * user_idアクセサー: member_idを返す
     * 
     * Laragearがuser_idを参照する際にmember_idを返す
     */
    public function getUserIdAttribute()
    {
        return $this->member_id;
    }
    
    /**
     * user_idミューテーター: member_idに設定
     * 
     * Laragearがuser_idを設定する際にmember_idに保存
     */
    public function setUserIdAttribute($value)
    {
        $this->attributes['member_id'] = $value;
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
