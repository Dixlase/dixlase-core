<?php
/*
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaptchaFormSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'route_name',
        'enabled',
        'plugin_name',
        'is_core',
        'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_core' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * 表示順でソートされたフォーム設定を取得
     */
    public static function getOrderedForms()
    {
        return static::orderBy('display_order')->orderBy('key')->get();
    }

    /**
     * 有効なフォーム設定のみを取得
     */
    public static function getEnabledForms()
    {
        return static::where('enabled', true)->orderBy('display_order')->orderBy('key')->get();
    }

    /**
     * 特定のフォームキーでreCAPTCHAが有効かチェック
     */
    public static function isEnabledFor(string $formKey): bool
    {
        return static::where('key', $formKey)->where('enabled', true)->exists();
    }

    /**
     * コア機能のフォーム設定のみを取得
     */
    public static function getCoreForms()
    {
        return static::where('is_core', true)->orderBy('display_order')->orderBy('key')->get();
    }

    /**
     * プラグインのフォーム設定のみを取得
     */
    public static function getPluginForms(?string $pluginName = null)
    {
        $query = static::where('is_core', false);
        
        if ($pluginName) {
            $query->where('plugin_name', $pluginName);
        }
        
        return $query->orderBy('display_order')->orderBy('key')->get();
    }

    /**
     * フォーム設定を作成または更新
     */
    public static function createOrUpdate(array $data)
    {
        return static::updateOrCreate(
            ['key' => $data['key']],
            $data
        );
    }
}
