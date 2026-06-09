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

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front page revision model
 *
 * Retains snapshots on save, manual backup, and pre-restore backup
 */
class FrontPageRevision extends Model
{
    use BelongsToSite;

    protected $table = 'front_page_revisions';

    public const TYPE_AUTO = 'auto';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_RESTORE_BACKUP = 'restore_backup';

    /** @var list<string> */
    protected $fillable = [
        'site_id',
        'front_page_id',
        'snapshot',
        'type',
        'note',
        'is_protected',
        'created_by',
    ];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'is_protected' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FrontPage, self>
     */
    public function frontPage(): BelongsTo
    {
        return $this->belongsTo(FrontPage::class);
    }

    /**
     * @return BelongsTo<Member, self>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (self $revision): void {
            if (empty($revision->created_at)) {
                $revision->created_at = now();
            }
        });
    }
}
