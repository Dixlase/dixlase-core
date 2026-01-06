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

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Enums\AuthenticationMode;
use App\Enums\AppearanceMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\Locale;
use App\Traits\HasPermissions;


class Member extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable, HasPermissions;


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
        'default_two_fa_method',
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
     * WebAuthn認証情報とのリレーション
     */
    public function webauthnCredentials()
    {
        return $this->hasMany(MemberTwoFaPasskey::class);
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
     * @param string|null $context 'create', 'email_change', または 'resend'。null の場合は pending_email の有無で自動判定
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
}
