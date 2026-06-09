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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Theme version history model
 *
 * Mirror of PluginVersionHistory for themes. Records theme installs,
 * updates, and rollbacks to defend against supply-chain attacks. Append-only
 * by convention; the slug-keyed (no-FK) retention policy is frozen for ^0.1
 * — see docs/development/supply-chain.md.
 */
class ThemeVersionHistory extends Model
{
    /**
     * @var string
     */
    protected $table = 'theme_version_history';

    public const METHOD_INSTALL = 'install';

    public const METHOD_UPDATE = 'update';

    public const METHOD_ROLLBACK = 'rollback';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'theme_slug',
        'old_version',
        'new_version',
        'old_signing_key_id',
        'new_signing_key_id',
        'old_author_id',
        'new_author_id',
        'files_changed_count',
        'lines_added',
        'lines_removed',
        'signing_key_changed',
        'author_id_changed',
        'installation_method',
        'installed_from_url',
        'applied_by_id',
        'applied_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'files_changed_count' => 'integer',
        'lines_added' => 'integer',
        'lines_removed' => 'integer',
        'signing_key_changed' => 'boolean',
        'author_id_changed' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /**
     * Relationship to the administrator (Member) who applied the change
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'applied_by_id');
    }
}
