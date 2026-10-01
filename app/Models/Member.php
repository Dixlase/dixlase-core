<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * Member model
 */
class Member extends Authenticatable implements MustVerifyEmail, PasskeyUser, TwoFaInterface
{
    use HasFactory, HasPermissions, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable, TwoFactorEnableCheck;

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
        'avatar_path',
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

    /**
     * Determine whether this member is permitted to authenticate.
     *
     * Reflects the account lifecycle state only. Credential verification
     * and email-verification checks are handled separately by the login
     * flow. Returns false when the status is missing, so the check is
     * fail-closed.
     */
    public function canAuthenticate(): bool
    {
        return $this->status?->canAuthenticate() ?? false;
    }

    public function trustedDevices()
    {
        // MembersTrustedDevice, not TrustedDevice: the model was renamed and
        // this reference was left behind. It is reached from
        // DeviceDetectionTrait::isDifferentEnvironment(), which TwoFaHelper
        // uses to decide whether to demand 2FA — and that call is guarded by
        // method_exists($user, 'trustedDevices'), which passes. The \Error
        // therefore landed inside the 2FA decision itself.
        //
        // The failure at least pointed the safe way: the branch it aborted is
        // the one that SKIPS 2FA for a recognised device, so a trusted device
        // was challenged rather than waved through.
        return $this->hasMany(MembersTrustedDevice::class);
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
     * Only the address-confirmation mail goes to pending_email. Everything
     * else -- password resets, login alerts -- stays on the confirmed address,
     * or a typo'd or hostile pending address would receive reset links for
     * the account before it was ever verified.
     *
     * @param  \Illuminate\Notifications\Notification|null  $notification
     * @return string
     */
    public function routeNotificationForMail($notification = null)
    {
        if ($notification instanceof \App\Notifications\MemberVerifyEmailNotification) {
            return $this->pending_email ?? $this->email;
        }

        return $this->email;
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
     *
     * Same relation as passkeys(); kept under the TwoFaInterface name that the
     * 2FA services and plugins use.
     */
    public function twoFaPasskeys(): HasMany
    {
        return $this->passkeys();
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
    // Passkeys (laravel/passkeys) PasskeyUser implementation
    // ========================================

    /**
     * Get the passkeys registered by the member.
     *
     * Overrides the trait default, which resolves the model from the global
     * Passkeys::passkeyModel() setting, so the member relation always points
     * at the members_passkeys table no matter what else is registered.
     *
     * @return HasMany<\Laravel\Passkeys\Passkey, \Illuminate\Database\Eloquent\Model>
     */
    public function passkeys(): HasMany
    {
        // Typed as the PasskeyUser contract declares it; Larastan would
        // otherwise infer HasMany<App\Models\Passkey, Member>, which its
        // invariant generics reject against the contract.
        /** @var HasMany<\Laravel\Passkeys\Passkey, \Illuminate\Database\Eloquent\Model> $relation */
        $relation = $this->hasMany(Passkey::class, 'member_id');

        return $relation;
    }

    /**
     * Name shown prominently in the authenticator's account picker.
     */
    public function getPasskeyDisplayName(): string
    {
        return (string) ($this->getAttribute('name') ?: ($this->getAttribute('account_name') ?: $this->getAttribute('email')));
    }

    /**
     * Account identifier shown under the display name in authenticator UIs.
     */
    public function getPasskeyUsername(): string
    {
        return (string) ($this->getAttribute('email') ?: $this->getAttribute('account_name'));
    }
}
