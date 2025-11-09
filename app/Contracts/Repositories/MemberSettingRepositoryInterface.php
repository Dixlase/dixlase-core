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

namespace App\Contracts\Repositories;

use App\Models\MemberSetting;

/**
 * メンバー設定リポジトリインターフェース
 */
interface MemberSettingRepositoryInterface
{
    /**
     * すべての設定を取得
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * 特定のキーの値を取得
     *
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 複数のキーの値を一括取得
     *
     * @param array<string> $keys 設定キーの配列
     * @param mixed $default デフォルト値
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * 設定値を保存
     *
     * @param string $key 設定キー
     * @param mixed $value 設定値
     * @return MemberSetting
     */
    public function set(string $key, mixed $value): MemberSetting;

    /**
     * 複数の設定値を一括保存
     *
     * @param array<string, mixed> $settings 設定の配列
     * @return bool
     */
    public function setMultiple(array $settings): bool;

    /**
     * 設定が存在するか確認
     *
     * @param string $key 設定キー
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 設定を削除
     *
     * @param string $key 設定キー
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * キャッシュをクリア
     *
     * @param string|null $key 特定のキーのみクリアする場合は指定
     * @return void
     */
    public function clearCache(?string $key = null): void;

    /**
     * すべてのキャッシュをクリア
     *
     * @return void
     */
    public function clearAllCache(): void;
}
