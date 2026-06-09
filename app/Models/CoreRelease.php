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
 * Core release state. Single-row table (id = 1) seeded during installation.
 * Mirrors the per-row update fields on the plugins/themes tables.
 */
class CoreRelease extends Model
{
    /**
     * Fixed primary key for the single-row state table.
     */
    public const PRIMARY_ID = 1;

    protected $fillable = [
        'source_id',
        'source_repo',
        'available_version',
        'last_notified_version',
        'available_version_published_at',
        'release_url',
        'release_notes',
        'last_version_check',
        'update_failed_at',
        'update_failure_reason',
        'signing_key_id',
        'author_id',
        'authority_key_id',
        'installed_from_url',
        'installation_method',
        'installed_at',
    ];

    protected $casts = [
        'available_version_published_at' => 'datetime',
        'last_version_check' => 'datetime',
        'update_failed_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    /**
     * Resolve the singleton state row, creating it if missing.
     */
    public static function singleton(): self
    {
        return self::query()->firstOrCreate(['id' => self::PRIMARY_ID]);
    }

    /**
     * Extension source the core was discovered from (when remote checks are enabled).
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Whether a newer core version has been discovered remotely.
     */
    public function hasUpdateAvailable(): bool
    {
        $current = (string) config('app.version', '0.0.0');

        return $this->available_version !== null
            && version_compare($this->available_version, $current, '>');
    }
}
