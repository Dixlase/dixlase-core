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

use App\Models\BaseSetting;

/**
 * 基本設定リポジトリインターフェース
 */
interface BaseSettingRepositoryInterface
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
     * @param string $name 設定名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * 複数のキーの値を一括取得
     *
     * @param array<string> $names 設定名の配列
     * @param mixed $default デフォルト値
     * @return array<string, mixed>
     */
    public function getMultiple(array $names, mixed $default = null): array;

    /**
     * 設定値を保存
     *
     * @param string $name 設定名
     * @param mixed $value 設定値（配列の場合は自動的にJSON化）
     * @return BaseSetting
     */
    public function set(string $name, mixed $value): BaseSetting;

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
     * @param string $name 設定名
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * 設定を削除
     *
     * @param string $name 設定名
     * @return bool
     */
    public function delete(string $name): bool;

    /**
     * OGP画像リレーションを含む設定を取得
     *
     * @param string $name 設定名
     * @return BaseSetting|null
     */
    public function findWithRelations(string $name): ?BaseSetting;
}
