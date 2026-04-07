<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Models\Traits\UsesSettingRepositoryTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * 基本設定モデル
 *
 * @deprecated 静的メソッドは非推奨です。BaseSettingRepositoryを使用してください。
 */
class BaseSetting extends Model
{
    use UsesSettingRepositoryTrait;

    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'base_settings';

    /**
     * ホワイトリスト
     *
     * @var array
     */
    protected $fillable = ['name', 'value'];

    /**
     * デフォルトOGP画像とのリレーション
     */
    public function defaultOgpImage()
    {
        return $this->belongsTo(Media::class, 'default_ogp_image_id');
    }

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return BaseSettingRepositoryInterface::class;
    }
}
