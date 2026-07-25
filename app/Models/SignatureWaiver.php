<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core use only. Do not reference from plugins/themes
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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operator-recorded signature waiver (overlay on the objective verifier result).
 *
 * A waiver suppresses the "invalid / unsigned" warning for an extension the
 * operator deliberately accepted (branded rebuild, local fork, dev work). It
 * does not change the underlying signature status — see SignatureWaiverService.
 *
 * Single-active is enforced by the NULL-safe `active` sentinel (1 = active,
 * NULL = revoked); see the create_signature_waivers_table migration.
 */
class SignatureWaiver extends Model
{
    public const SCOPE_PLUGIN = 'plugin';

    public const SCOPE_THEME = 'theme';

    public const SCOPE_CORE = 'core';

    /**
     * Scopes that may carry a waiver.
     */
    public const SCOPES = [
        self::SCOPE_PLUGIN,
        self::SCOPE_THEME,
        self::SCOPE_CORE,
    ];

    protected $table = 'signature_waivers';

    protected $fillable = [
        'scope',
        'target_slug',
        'waived_at',
        'waived_by',
        'waived_by_label',
        'reason',
        'revoked_at',
        'revoked_by',
        'revoked_reason',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'waived_at' => 'datetime',
            'revoked_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /**
     * Limit the query to active (not yet revoked) waivers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Limit the query to a specific scope + target.
     */
    public function scopeForTarget(Builder $query, string $scope, string $targetSlug): Builder
    {
        return $query->where('scope', $scope)->where('target_slug', $targetSlug);
    }
}
