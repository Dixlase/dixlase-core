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
use App\Contracts\Repositories\BaseSettingRepositoryInterface;

/**
 * 基本設定モデル
 * 
 * @deprecated 静的メソッドは非推奨です。BaseSettingRepositoryを使用してください。
 */
class BaseSetting extends Model
{
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
     * 設定の取得
     *
     * @deprecated BaseSettingRepository::get() を使用してください
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($name, $default = null)
    {
        return app(BaseSettingRepositoryInterface::class)->get($name, $default);
    }

    /**
     * 設定の保存
     *
     * @deprecated BaseSettingRepository::set() を使用してください
     * @param string $name
     * @param mixed $value
     * @return BaseSetting
     */
    public static function setValue($name, $value)
    {
        return app(BaseSettingRepositoryInterface::class)->set($name, $value);
    }

    /**
     * 複数の設定を一括保存
     *
     * @deprecated BaseSettingRepository::setMultiple() を使用してください
     * @param array $settings
     * @return void
     */
    public static function setMany(array $settings): void
    {
        app(BaseSettingRepositoryInterface::class)->setMultiple($settings);
    }

    /**
     * ConfigHelperとの互換性のためのエイリアスメソッド
     *
     * @deprecated BaseSettingRepository::get() を使用してください
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        return app(BaseSettingRepositoryInterface::class)->get($name, $default);
    }
}
