<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionGrantType;
use App\Models\Traits\BelongsToSite;
use App\Services\CustomRolePermissionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant or deny of a custom role, on one site.
 *
 * `permission` holds either a core Permission value (`members.view`) or a
 * plugin menu key written as `plugin:<PluginDirectory>:<menu.key>` (see
 * CustomRolePermissionResolver::pluginKey()). The prefix keeps a plugin key
 * from ever being read as a core permission of the same spelling.
 *
 * @property int $id
 * @property int $site_id
 * @property int $custom_role_id
 * @property string $permission
 * @property PermissionGrantType $grant_type
 * @property int|null $updated_by
 */
final class CustomRolePermissionOverride extends Model
{
    use BelongsToSite;

    protected $table = 'custom_role_permission_overrides';

    protected $fillable = [
        'site_id',
        'custom_role_id',
        'permission',
        'grant_type',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'custom_role_id' => 'integer',
            'site_id' => 'integer',
            'grant_type' => PermissionGrantType::class,
        ];
    }

    protected static function booted(): void
    {
        $forget = static function (CustomRolePermissionOverride $override): void {
            CustomRolePermissionResolver::forget($override->custom_role_id, $override->site_id);
        };

        self::saved($forget);
        self::deleted($forget);
    }

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(CustomRole::class, 'custom_role_id');
    }

    public function updatedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'updated_by');
    }
}
