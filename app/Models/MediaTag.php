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

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A tag of the media library (#766).
 *
 * Flat and per site. A file carries any number of tags, and a tag groups
 * any number of files; neither owns the other.
 *
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property int|null $media_count Set by scopeWithMediaCount()
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MediaTag extends Model
{
    use BelongsToSite;

    /**
     * Longest name the column holds.
     */
    public const MAX_NAME_LENGTH = 50;

    /**
     * Most tags one request may name, for a file or for a bulk change.
     */
    public const MAX_TAGS_PER_REQUEST = 30;

    protected $table = 'media_tags';

    protected $fillable = [
        'site_id',
        'name',
    ];

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_media_tag', 'media_tag_id', 'media_id');
    }

    /**
     * Tags in name order, each with the number of files that carry it.
     * Trashed files are not counted: Media's own scopes apply.
     *
     * @param  Builder<MediaTag>  $query
     * @return Builder<MediaTag>
     */
    public function scopeWithMediaCount(Builder $query): Builder
    {
        return $query->withCount('media')->orderBy('name');
    }

    /**
     * The form a tag name is stored in: every run of whitespace
     * (full-width spaces included) folded into one space, control and
     * bidirectional override characters removed, and trimmed.
     */
    public static function normalizeName(string $name): string
    {
        $name = (string) preg_replace('/[\s\x{3000}]+/u', ' ', $name);
        $name = (string) preg_replace('/[\p{Cc}\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', '', $name);

        return trim($name);
    }
}
