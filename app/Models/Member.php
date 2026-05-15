<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
 * Member model
 */
class Member extends Authenticatable implements MustVerifyEmail, TwoFaInterface, WebAuthnAuthenticatable
{
    use HasFactory, HasPermissions, Notifiable, SoftDeletes, TwoFactorAuthenticatable, TwoFactorEnableCheck;

    /**
     * Table name definition
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
        'sidebar_preferences' => 'array',
        'getting_started_visited' => 'array',
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
        'sidebar_preferences',
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
        // Create custom password reset notification
        $this->notify(new \App\Notifications\AdminResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @param  string|null  $context  'create', 'email_change', or 'resend'. If null, automatically determined by presence of pending_email
     * @return void
     */
    public function sendEmailVerificationNotification(?string $context = null)
    {
        // If context is not specified, determined by presence of pending_email
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
        // Use pending_email if present, otherwise use regular email
        return $this->pending_email ?? $this->email;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        // Change email notification destination to pending_email (when changing email address)
        return $this->pending_email ?? $this->email;
    }

    // ========================================
    // TwoFaInterface Implementation
    // ========================================

    /**
     * Get user ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? $this->account_name ?? $this->email;
    }

    /**
     * Get account name
     */
    public function getAccountName(): ?string
    {
        return $this->account_name;
    }

    /**
     * Get two-factor authentication mode
     */
    public function getTwoFaMode(): int
    {
        $mode = $this->two_fa_mode;

        return $mode instanceof AuthenticationMode ? $mode->value : (int) ($mode ?? 0);
    }

    /**
     * Whether passkey is enabled
     */
    public function isTwoFaPasskeyEnabled(): bool
    {
        return ($this->two_fa_passkey_enabled ?? true) === true;
    }

    /**
     * Get default two-factor authentication method
     */
    public function getTwoFaDefaultMethod(): int
    {
        return $this->two_fa_default_method ?? 1;
    }

    /**
     * Relation to passkey credentials
     */
    public function twoFaPasskeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\WebAuthnCredential::class, 'member_id');
    }

    /**
     * Relation to recovery codes
     */
    public function twoFaRecoveryCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaRecoveryCode::class, 'member_id');
    }

    /**
     * Relation to two-factor authentication attempts
     */
    public function twoFaAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaAttempt::class, 'member_id');
    }

    /**
     * Relation to two-factor authentication tokens
     */
    public function twoFaTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MemberTwoFaToken::class, 'member_id');
    }

    // ========================================
    // WebAuthn (Laragear) interface implementation
    // ========================================

    /**
     * Return user data for WebAuthn
     */
    public function webAuthnData(): WebAuthnData
    {
        return new WebAuthnData(
            name: $this->email,
            displayName: $this->name ?? $this->account_name,
        );
    }

    /**
     * Return anonymized user ID (UUID) for WebAuthn
     *
     * Generate consistent UUID from user ID (using UUID v5)
     */
    public function webAuthnId(): \Ramsey\Uuid\UuidInterface
    {
        // Generate consistent UUID from user ID
        // Use DNS namespace as namespace and user ID as name
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'dixlase.member.'.$this->id);
    }

    /**
     * WebAuthn credentials relation
     *
     * Use webauthn_credentials table
     */
    public function webAuthnCredentials(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(WebAuthnCredential::class, 'authenticatable');
    }

    /**
     * Delete all WebAuthn credentials
     */
    public function flushCredentials(string ...$except): void
    {
        $this->webAuthnCredentials()
            ->when($except, fn ($query) => $query->whereNotIn('id', $except))
            ->delete();
    }

    /**
     * Disable all WebAuthn credentials
     */
    public function disableAllCredentials(string ...$except): void
    {
        $this->webAuthnCredentials()
            ->when($except, fn ($query) => $query->whereNotIn('id', $except))
            ->update(['disabled_at' => now()]);
    }

    /**
     * Create WebAuthn credential instance
     */
    public function makeWebAuthnCredential(array $properties): WebAuthnCredential
    {
        return $this->webAuthnCredentials()->make($properties);
    }
}
