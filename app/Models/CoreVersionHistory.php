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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail of core version transitions.
 * Mirrors plugin_version_history for parity.
 */
class CoreVersionHistory extends Model
{
    protected $table = 'core_version_history';

    public const METHOD_INSTALL = 'install';

    public const METHOD_UPDATE = 'update';

    public const METHOD_ROLLBACK = 'rollback';

    protected $fillable = [
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

    protected $casts = [
        'signing_key_changed' => 'boolean',
        'author_id_changed' => 'boolean',
        'files_changed_count' => 'integer',
        'lines_added' => 'integer',
        'lines_removed' => 'integer',
        'applied_at' => 'datetime',
    ];

    /**
     * The administrator who applied this transition.
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'applied_by_id');
    }
}
