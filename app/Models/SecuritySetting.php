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
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;

/**
 * セキュリティ設定モデル
 * 
 * @deprecated 静的メソッドは非推奨です。SecuritySettingRepositoryを使用してください。
 */
class SecuritySetting extends Model
{
    protected $table = 'security_settings';

    protected $fillable = [
        'name',
        'value'
    ];

    /**
     * 設定値を取得
     *
     * @deprecated SecuritySettingRepository::get() を使用してください
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return app(SecuritySettingRepositoryInterface::class)->get($key, $default);
    }

    /**
     * 設定値を保存
     *
     * @deprecated SecuritySettingRepository::set() を使用してください
     * @param string $key
     * @param mixed $value
     * @return SecuritySetting
     */
    public static function set($key, $value)
    {
        return app(SecuritySettingRepositoryInterface::class)->set($key, $value);
    }
}
