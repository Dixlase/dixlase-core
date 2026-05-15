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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laragear\WebAuthn\Models\WebAuthnCredential as BaseWebAuthnCredential;

/**
 * WebAuthn credential record (extends Laragear\WebAuthn base model).
 * Plugins/themes that handle passkey/WebAuthn authentication may reference this model directly.
 */
class WebAuthnCredential extends BaseWebAuthnCredential
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webauthn_credentials';

    /**
     * Initialize the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set member_id and name before saving (Laragear\WebAuthn sets authenticatable_id and alias)
        static::creating(function ($model) {
            // Set member_id from authenticatable_id
            if (empty($model->member_id) && ! empty($model->authenticatable_id)) {
                $model->member_id = $model->authenticatable_id;
            }
            // Set name from alias
            if (empty($model->name) && ! empty($model->alias)) {
                $model->name = $model->alias;
            }
        });
    }

    /**
     * user_id mutator: auto-generate from member_id
     */
    public function setUserIdAttribute($value)
    {
        // Use the value as-is if already set
        if (! empty($value)) {
            $this->attributes['user_id'] = $value;

            return;
        }

        // Generate UUID from member_id
        if (! empty($this->member_id)) {
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
        'public_key' => 'string', // Override parent class encrypted cast
        'transports' => 'json',
        'certificates' => 'json',
        'disabled_at' => 'datetime',
    ];

    /**
     * Get the casts array.
     * Completely disable parent class encrypted cast
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();
        // Remove encrypted cast for public_key
        unset($casts['public_key']);
        // Treat as a regular string
        $casts['public_key'] = 'string';

        return $casts;
    }

    /**
     * public_key accessor: completely bypass encryption
     */
    public function getPublicKeyAttribute($value)
    {
        // Bypass parent class encrypted cast and return raw value
        return $this->attributes['public_key'] ?? $value;
    }

    /**
     * public_key mutator: save without encryption
     */
    public function setPublicKeyAttribute($value)
    {
        // Save as-is without encryption
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
     * Update last used timestamp
     */
    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Update device name
     */
    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->save();
    }
}
