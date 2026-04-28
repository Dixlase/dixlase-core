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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Models\Traits\UsesSettingRepositoryTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * フロント設定モデル
 *
 * @deprecated 静的メソッドは非推奨です。FrontSettingRepositoryを使用してください。
 */
class FrontSetting extends Model
{
    use UsesSettingRepositoryTrait;

    protected $table = 'front_settings';

    protected $fillable = [
        'name',
        'value',
        'front_ogp_image_id',
    ];

    /**
     * フロントOGP画像とのリレーション
     */
    public function frontOgpImage()
    {
        return $this->belongsTo(Media::class, 'front_ogp_image_id');
    }

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return FrontSettingRepositoryInterface::class;
    }
}
