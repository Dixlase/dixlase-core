<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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
use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use App\Models\Traits\UsesSettingRepositoryTrait;

/**
 * API設定モデル
 * 
 * @deprecated 静的メソッドは非推奨です。ApiSettingRepositoryを使用してください。
 */
class ApiSetting extends Model
{
    use UsesSettingRepositoryTrait;

    protected $table = 'api_settings';

    protected $fillable = [
        'name',
        'value'
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return ApiSettingRepositoryInterface::class;
    }
}
