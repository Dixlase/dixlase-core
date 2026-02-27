<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Models;

use App\Contracts\TwoFaInterface;
use App\Enums\AppearanceMode;
use App\Enums\AuthenticationMode;
use App\Enums\Locale;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Traits\HasPermissions;
use App\Traits\TwoFa\TwoFactorEnableCheck;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnData;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Ramsey\Uuid\Uuid;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * メンバーモデル
 */
class Member extends Authenticatable implements MustVerifyEmail, TwoFaInterface, WebAuthnAuthenticatable
{
    use HasFactory, HasPermissions, Notifiable, SoftDeletes, TwoFactorAuthenticatable, TwoFactorEnableCheck;

    /**
     * テーブル名の定義
     */
    protected $table = 'members';

    protected $primaryKey = 'id';

    protected $casts = [
        'password' => 'hashed',
        'role' => MemberRole::class,
        'status' => MemberStatus::class,
        'login_notification_mode' => AuthenticationMode::class,
        'two_fa_mode' => AuthenticationMode::class,
        'appearance' => AppearanceMode::class,
        'locale' => Locale::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_name',
        'display_name',
        'email',
        'email_verified_at',
        'pending_email',
        'locale',
        'password',
        'role',
        'appearance',
        'status',
        'login_notification_mode',
        'two_fa_mode',
        'two_fa_passkey_enabled',
        'passkey_prompt_dismissed',
        'two_fa_default_method',
        'description',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function trustedDevices()
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        // カスタムパスワードリセット通知を作成
        $this->notify(new \App\Notifications\AdminResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @param  string|null  $context  'create', 'email_change', または 'resend'。null の場合は pending_email の有無で自動判定
     * @return void
     */
    public function sendEmailVerificationNotification(?string $context = null)
    {
        // コンテキストが指定されていない場合は pending_email の有無で判定
        if ($context === null) {
            $context = $this->pending_email ? 'email_change' : 'create';
        }

        $this->notify(new \App\Notifications\MemberVerifyEmailNotification($context));
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        // pending_email がある場合はそちらを使用、なければ通常のemail
        return $this->pending_email ?? $this->email;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        // メール通知の送信先をpending_emailに変更（メールアドレス変更時）
        return $this->pending_email ?? $this->email;
    }

    // ========================================
    // TwoFaInterface Implementation
    // ========================================

    /**
     * ユーザーIDを取得
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * メールアドレスを取得
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * 表示名を取得
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? $this->account_name ?? $this->email;
    }

    /**
     * アカウント名を取得
     */
    public function getAccountName(): ?string
    {
        return $this->account_name;
    }

    /**
     * 二段階認証モードを取得
     */
    public function getTwoFaMode(): AuthenticationMode|int
    {
        return $this->two_fa_mode ?? 0;
    }

    /**
     * パスキーが有効かどうか
     */
    public function isTwoFaPasskeyEnabled(): bool
    {
        return ($this->two_fa_passkey_enabled ?? true) === true;
    }

    /**
     * デフォルトの二段階認証方法を取得
     */
    public function getTwoFaDefaultMethod(): int
    {
        return $this->two_fa_default_method ?? 1;
    }

    /**
     * パスキー認証情報とのリレーション
     */
    public function twoFaPasskeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\WebAuthnCredential::class, 'member_id');
    }

    /**
     * 回復コードのリレーション
     */
    public function twoFaRecoveryCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaRecoveryCode::class, 'member_id');
    }

    /**
     * 二段階認証試行のリレーション
     */
    public function twoFaAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaAttempt::class, 'member_id');
    }

    /**
     * 二段階認証トークンのリレーション
     */
    public function twoFaTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaToken::class, 'member_id');
    }

    // ========================================
    // WebAuthn (Laragear) インターフェース実装
    // ========================================

    /**
     * WebAuthn用のユーザーデータを返す
     */
    public function webAuthnData(): WebAuthnData
    {
        return new WebAuthnData(
            name: $this->email,
            displayName: $this->name ?? $this->account_name,
        );
    }

    /**
     * WebAuthn用の匿名化されたユーザーID（UUID）を返す
     *
     * ユーザーIDから一貫したUUIDを生成（UUID v5を使用）
     */
    public function webAuthnId(): \Ramsey\Uuid\UuidInterface
    {
        // ユーザーIDから一貫したUUIDを生成
        // 名前空間にDNS名前空間を使用し、ユーザーIDを名前として使用
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'dixlase.member.'.$this->id);
    }

    /**
     * WebAuthn認証情報のリレーション
     *
     * webauthn_credentialsテーブルを使用
     */
    public function webAuthnCredentials(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(WebAuthnCredential::class, 'authenticatable');
    }

    /**
     * すべてのWebAuthn認証情報を削除
     */
    public function flushCredentials(string ...$except): void
    {
        $this->webAuthnCredentials()
            ->when($except, fn ($query) => $query->whereNotIn('id', $except))
            ->delete();
    }

    /**
     * すべてのWebAuthn認証情報を無効化
     */
    public function disableAllCredentials(string ...$except): void
    {
        $this->webAuthnCredentials()
            ->when($except, fn ($query) => $query->whereNotIn('id', $except))
            ->update(['disabled_at' => now()]);
    }

    /**
     * WebAuthn認証情報のインスタンスを作成
     */
    public function makeWebAuthnCredential(array $properties): WebAuthnCredential
    {
        return $this->webAuthnCredentials()->make($properties);
    }
}
