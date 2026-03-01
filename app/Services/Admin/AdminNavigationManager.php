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

namespace App\Services\Admin;

use App\Contracts\Admin\AdminNavigationManagerInterface;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 管理画面ナビゲーション管理の具象実装
 *
 * プラグインのナビゲーション設定をコアの config('admin.navigation') にマージする。
 * _insert_before / _insert_after による順序制御、children のマージをサポートする。
 */
class AdminNavigationManager implements AdminNavigationManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function mergeNavigationFile(string $configFile): void
    {
        if (! file_exists($configFile)) {
            return;
        }

        $pluginNavigation = require $configFile;

        if (! is_array($pluginNavigation)) {
            return;
        }

        foreach ($pluginNavigation as $key => $value) {
            $this->mergeNavigationItem($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mergeNavConfig(string $configFile): void
    {
        if (! file_exists($configFile)) {
            return;
        }

        $pluginConfig = require $configFile;

        if (! isset($pluginConfig['nav']) || ! is_array($pluginConfig['nav'])) {
            return;
        }

        foreach ($pluginConfig['nav'] as $key => $value) {
            $this->mergeNavigationItem($key, $value);
        }
    }

    /**
     * 単一のナビゲーション項目をマージ
     *
     * @param  string  $key  ナビゲーションキー
     * @param  array<string, mixed>  $value  ナビゲーション項目の設定
     */
    private function mergeNavigationItem(string $key, array $value): void
    {
        if (isset($value['_insert_before'])) {
            $this->insertOrdered('admin.navigation', $key, $value, $value['_insert_before'], 'before');
        } elseif (isset($value['_insert_after'])) {
            $this->insertOrdered('admin.navigation', $key, $value, $value['_insert_after'], 'after');
        } else {
            $existingValue = config("admin.navigation.{$key}");
            if ($existingValue !== null && is_array($existingValue)) {
                // 既存の設定がある場合、childrenのみをマージし、他のプロパティは保持
                if (isset($value['children']) && is_array($value['children'])) {
                    $existingChildren = $existingValue['children'] ?? [];
                    $existingValue['children'] = array_merge($existingChildren, $value['children']);
                }
                config(["admin.navigation.{$key}" => $existingValue]);
            } else {
                // 新規追加
                config(["admin.navigation.{$key}" => $value]);
            }
        }
    }

    /**
     * 指定されたキーの前後に要素を挿入する
     *
     * @param  string  $configKey  config() に格納するキー
     * @param  string  $insertKey  挿入するキー
     * @param  array<string, mixed>  $insertValue  挿入するデータ
     * @param  string  $targetKey  どのキーの前後に挿入するか
     * @param  string  $position  'before' or 'after'
     */
    private function insertOrdered(string $configKey, string $insertKey, array $insertValue, string $targetKey, string $position = 'before'): void
    {
        unset($insertValue['_insert_before'], $insertValue['_insert_after']);

        $existingConfig = config($configKey, []);

        $newConfig = [];
        $inserted = false;

        foreach ($existingConfig as $key => $value) {
            if ($position === 'before' && $key === $targetKey) {
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }

            $newConfig[$key] = $value;

            if ($position === 'after' && $key === $targetKey) {
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }
        }

        // ターゲットキーが存在しない場合は最後に追加
        if (! $inserted) {
            $newConfig[$insertKey] = $insertValue;
        }

        config([$configKey => $newConfig]);
    }
}
