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

        // 保存前にmember_idとnameを自動設定（Laragear\WebAuthnがauthenticatable_idとaliasを設定する）
        static::creating(function ($model) {
            // authenticatable_idからmember_idを設定
            if (empty($model->member_id) && !empty($model->authenticatable_id)) {
                $model->member_id = $model->authenticatable_id;
            }
            // aliasからnameを設定
            if (empty($model->name) && !empty($model->alias)) {
                $model->name = $model->alias;
            }
        });
    }
    
    /**
     * user_idミューテーター: member_idから自動生成
     */
    public function setUserIdAttribute($value)
    {
        // 値が設定されている場合はそのまま使用
        if (!empty($value)) {
            $this->attributes['user_id'] = $value;
            return;
        }
        
        // member_idからUUIDを生成
        if (!empty($this->member_id)) {
            $member = \App\Models\Member::find($this->member_id);
            if ($member) {
                $this->attributes['user_id'] = $member->webAuthnId()->toString();
            }
        }
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
        'user_id',
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
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'public_key' => 'string', // 親クラスのencryptedキャストを上書き
        'transports' => 'json',
        'certificates' => 'json',
        'disabled_at' => 'datetime',
    ];

    /**
     * Get the casts array.
     * 親クラスのencryptedキャストを完全に無効化
     *
     * @return array
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();
        // public_keyの暗号化キャストを削除
        unset($casts['public_key']);
        // 通常の文字列として扱う
        $casts['public_key'] = 'string';
        return $casts;
    }

    /**
     * public_keyアクセサ: 暗号化を完全にバイパス
     */
    public function getPublicKeyAttribute($value)
    {
        // 親クラスのencryptedキャストをバイパスして、生の値を返す
        return $this->attributes['public_key'] ?? $value;
    }

    /**
     * public_keyミューテーター: 暗号化せずに保存
     */
    public function setPublicKeyAttribute($value)
    {
        // 暗号化せずにそのまま保存
        $this->attributes['public_key'] = $value;
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
