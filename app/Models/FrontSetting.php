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

class FrontSetting extends Model
{
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
     * 設定値の取得
     */
    public static function getValue($name, $default = null)
    {
        $setting = self::where('name', $name)->first();
        return $setting ? ($setting->value ?? $default) : $default;
    }
    
    /**
     * 設定値の保存
     */
    public static function setValue($name, $value)
    {
        self::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );
    }
}
