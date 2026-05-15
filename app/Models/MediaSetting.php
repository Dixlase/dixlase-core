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

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Models\Traits\BelongsToSite;
use App\Models\Traits\UsesSettingRepositoryTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Media settings model
 *
 * @deprecated Static methods are deprecated. Use MediaSettingRepository instead.
 */
class MediaSetting extends Model
{
    use BelongsToSite;
    use UsesSettingRepositoryTrait;

    protected $table = 'media_settings';

    protected $fillable = [
        'site_id',
        'name',
        'value',
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return MediaSettingRepositoryInterface::class;
    }
}
