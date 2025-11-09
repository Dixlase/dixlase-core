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
use App\Contracts\Repositories\MediaSettingRepositoryInterface;

/**
 * メディア設定モデル
 * 
 * @deprecated 直接使用は非推奨です。MediaSettingRepositoryを使用してください。
 */
class MediaSetting extends Model
{
    protected $fillable = ['name', 'value'];

    /**
     * 設定値を取得
     *
     * @deprecated MediaSettingRepository::get() を使用してください
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        return app(MediaSettingRepositoryInterface::class)->get($name, $default);
    }

    /**
     * 設定値を保存
     *
     * @deprecated MediaSettingRepository::set() を使用してください
     * @param string $name
     * @param mixed $value
     * @return MediaSetting
     */
    public static function setValue(string $name, mixed $value): MediaSetting
    {
        return app(MediaSettingRepositoryInterface::class)->set($name, $value);
    }
}
