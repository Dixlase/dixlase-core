<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Extension Source Model
 *
 * Represents an external source for downloading and updating extensions.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $base_url
 * @property ?string $owner
 * @property ?string $auth_token
 * @property bool $is_enabled
 * @property bool $is_official
 * @property ?string $official_signature
 * @property int $priority
 * @property ?array<string, mixed> $settings
 * @property ?\Illuminate\Support\Carbon $last_checked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ExtensionSource extends Model
{
    protected $table = 'extension_sources';

    protected $fillable = [
        'name',
        'type',
        'base_url',
        'owner',
        'auth_token',
        'is_enabled',
        'is_official',
        'official_signature',
        'priority',
        'settings',
        'last_checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auth_token' => 'encrypted',
            'is_enabled' => 'boolean',
            'is_official' => 'boolean',
            'priority' => 'integer',
            'settings' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'auth_token',
    ];

    // ========================================
    // Relations
    // ========================================

    /**
     * Plugins installed from this source
     */
    public function plugins(): HasMany
    {
        return $this->hasMany(Plugin::class, 'source_id');
    }

    /**
     * Themes installed from this source
     */
    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class, 'source_id');
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Filter enabled sources ordered by priority
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->orderBy('priority');
    }

    /**
     * Filter official sources
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }

    /**
     * Filter by provider type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Check if this source has authentication configured
     */
    public function hasAuthentication(): bool
    {
        return ! is_null($this->auth_token);
    }

    /**
     * Check if this source has a valid official signature
     */
    public function hasSignature(): bool
    {
        return ! is_null($this->official_signature);
    }

    /**
     * Mark the last check timestamp
     */
    public function markChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }
}
