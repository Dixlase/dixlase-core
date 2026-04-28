<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
                    $existingValue['children'] = $this->mergeChildren($existingChildren, $value['children']);
                }
                config(["admin.navigation.{$key}" => $existingValue]);
            } else {
                // 新規追加
                config(["admin.navigation.{$key}" => $value]);
            }
        }
    }

    /**
     * children 配列を再帰的にマージする
     *
     * 既存の子要素のプロパティ（text, icon, route 等）を保持しつつ、
     * 新しい children をマージする。新規キーはそのまま追加する。
     *
     * @param  array<string, mixed>  $existingChildren  既存の children 配列
     * @param  array<string, mixed>  $newChildren  マージする children 配列
     * @return array<string, mixed> マージ結果
     */
    private function mergeChildren(array $existingChildren, array $newChildren): array
    {
        foreach ($newChildren as $childKey => $childValue) {
            if (isset($existingChildren[$childKey]) && is_array($existingChildren[$childKey]) && is_array($childValue)) {
                // 既存の子要素がある場合、children を再帰マージし、他のプロパティは上書き
                if (isset($childValue['children']) && is_array($childValue['children'])) {
                    $existingGrandchildren = $existingChildren[$childKey]['children'] ?? [];
                    $existingChildren[$childKey]['children'] = $this->mergeChildren($existingGrandchildren, $childValue['children']);
                }

                // children 以外のプロパティが指定されていれば上書き
                foreach ($childValue as $prop => $propValue) {
                    if ($prop !== 'children') {
                        $existingChildren[$childKey][$prop] = $propValue;
                    }
                }
            } else {
                // 新規の子要素はそのまま追加
                $existingChildren[$childKey] = $childValue;
            }
        }

        return $existingChildren;
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
