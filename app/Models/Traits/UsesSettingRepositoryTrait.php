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

namespace App\Models\Traits;

use App\Contracts\Repositories\SettingRepositoryInterface;

/**
 * 設定モデル用Trait
 * 
 * 設定系モデルで共通の静的メソッドを提供します。
 * これらのメソッドは後方互換性のために残されていますが、
 * 新しいコードではRepositoryを直接使用することを推奨します。
 * 
 * @deprecated 静的メソッドは非推奨です。対応するRepositoryを使用してください。
 */
trait UsesSettingRepositoryTrait
{
    /**
     * リポジトリインターフェースのクラス名を取得
     * 
     * @return string
     */
    abstract protected static function getRepositoryInterface(): string;

    /**
     * すべての設定を取得
     * 
     * @deprecated Repository::all() を使用してください
     * @return array<string, mixed>
     */
    public static function getAllSettings(): array
    {
        return app(static::getRepositoryInterface())->all();
    }

    /**
     * 設定値を取得
     * 
     * @deprecated Repository::get() を使用してください
     * @param string $name 設定名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        return app(static::getRepositoryInterface())->get($name, $default);
    }

    /**
     * 設定値を保存
     * 
     * @deprecated Repository::set() を使用してください
     * @param string $name 設定名
     * @param mixed $value 設定値
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function setValue(string $name, mixed $value): \Illuminate\Database\Eloquent\Model
    {
        return app(static::getRepositoryInterface())->set($name, $value);
    }

    /**
     * 複数の設定を一括保存
     * 
     * @deprecated Repository::setMultiple() を使用してください
     * @param array<string, mixed> $settings 設定の配列
     * @return void
     */
    public static function setMany(array $settings): void
    {
        app(static::getRepositoryInterface())->setMultiple($settings);
    }

    /**
     * SecuritySetting互換: get()メソッド
     * 
     * @deprecated Repository::get() を使用してください
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    /**
     * SecuritySetting互換: set()メソッド
     * 
     * @deprecated Repository::set() を使用してください
     * @param string $key 設定キー
     * @param mixed $value 設定値
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function set(string $key, mixed $value): \Illuminate\Database\Eloquent\Model
    {
        return static::setValue($key, $value);
    }
}
