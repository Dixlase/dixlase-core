<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * テーマメタデータモデル
 */
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
        'package_name', // パッケージ名
        'directory',    // テーマディレクトリ名
        'slug',         // テーマのスラッグ名 (一意)
        'namespace',    // テーマの名前空間
        'description',  // テーマ説明
        'license',      // ライセンス
        'author',       // 作者
        'email',        // 作者のメール
        'url',          // 作者のウェブサイト
        'version',      // テーマバージョン
        'has_settings', // テーマ設定ページの有無
        'config',       // テーマ設定
        'installed_at', // インストール日時
        'source_id',
        'source_repo',
        'available_version',
        'last_version_check',
    ];

    /**
     * キャスト設定
     */
    protected $casts = [
        'config' => 'array',
        'has_settings' => 'boolean',
        'installed_at' => 'datetime',
        'last_version_check' => 'datetime',
    ];

    /**
     * Extension source that this theme was installed from
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Check if an update is available from the source
     */
    public function hasUpdateAvailable(): bool
    {
        return $this->available_version !== null
            && version_compare($this->available_version, $this->version, '>');
    }

    /**
     * インストール済みテーマのスコープ
     */
    public function scopeInstalled($query)
    {
        return $query->whereNotNull('installed_at');
    }

    /**
     * テーマがインストール済みかチェック
     */
    public function isInstalled(): bool
    {
        return ! is_null($this->installed_at);
    }

    /**
     * テーマが有効化されているかチェック
     */
    public function isEnabled(): bool
    {
        $themeSetting = \DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $enabledThemeId = $themeSetting ? (int) $themeSetting->value : null;

        return $enabledThemeId && $this->id == $enabledThemeId;
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
            throw new \Exception('デフォルトテーマは削除できません。');
        }

        $this->delete();
    }
}
