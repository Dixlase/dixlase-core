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

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Media model
 */
class Media extends Model
{
    use BelongsToSite, HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'site_id',
        'name',
        'caption',
        'description',
        'alt_text',
        'path',
        'type',
        'file_size',
        'width',
        'height',
        'uploaded_by',
    ];

    /**
     * Accessor that returns formatted file size
     */
    protected function formattedFileSize(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->file_size === null) {
                    return null;
                }

                $bytes = (int) $this->file_size;
                if ($bytes === 0) {
                    return '0 B';
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $i = (int) floor(log($bytes, 1024));

                return round($bytes / pow(1024, $i), $i > 0 ? 1 : 0).' '.$units[$i];
            },
        );
    }

    /**
     * Accessor that returns formatted image dimensions
     */
    protected function formattedDimensions(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->width === null || $this->height === null) {
                    return null;
                }

                return $this->width.' × '.$this->height.' px';
            },
        );
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'uploaded_by');
    }
}
