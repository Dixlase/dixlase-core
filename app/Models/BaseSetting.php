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
use Illuminate\Support\Facades\Log;

class BaseSetting extends Model
{
    /**
     * デフォルトOGP画像とのリレーション
     */
    public function defaultOgpImage()
    {
        return $this->belongsTo(Media::class, 'default_ogp_image_id');
    }
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
     * 設定の取得
     *
     * @param string $title
     * @param mixed $default
     * @return mixed
     */

    public static function getValue($name, $default = null)
    {
        $setting = self::where('name', $name)->first();
        return $setting ? json_decode($setting->value, true) ?? $setting->value : $default;
    }

    /**
     * 設定の保存
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function setValue($name, $value)
    {
        $setting = self::where('name', $name)->first();

        if ($setting) {
            // 既存の設定がある場合は更新
            $setting->value = is_array($value) ? json_encode($value) : $value;
            $setting->save();
        } else {
            // 設定がない場合は新規作成
            self::create([
                'name' => $name,
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);
        }
    }

    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value);
        }
    }

    /**
     * ConfigHelperとの互換性のためのエイリアスメソッド
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        return self::getValue($name, $default);
    }
}
