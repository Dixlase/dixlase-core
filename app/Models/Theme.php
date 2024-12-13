<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Theme extends Model
{
    use HasFactory;

    /**
     * テーブル名の定義
     */
    protected $table = 'themes';

    /**
     * 複数代入の許可フィールド
     */
    protected $fillable = [
        'name',         // テーマ名
        'slug',         // テーマのスラッグ名 (一意)
        'directory',    // テーマディレクトリ名
        'version',      // テーマバージョン

    ];


    /**
     * アクティブテーマのスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * デフォルトテーマの取得
     */
    public static function getDefaultTheme()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * テーマの削除を防ぐ（デフォルトテーマは削除不可）
     */
    public function deleteTheme()
    {
        if ($this->is_default) {
            throw new \Exception("デフォルトテーマは削除できません。");
        }

        $this->delete();
    }
}
