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

namespace App\Traits;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 設定ファイルローディングユーティリティ
 */
trait ConfigLoaderTrait
{
    /**
     * コンフィグファイルを読み込む
     */
    private function loadConfigFiles($path)
    {
        $configs = [];

        if (is_dir($path)) {
            foreach (glob($path.'/*.php') as $file) {
                $key = basename($file, '.php');
                $configs[$key] = require $file;
            }
        }

        return $configs;
    }

    /**
     * 全体のコンフィグ配列を再帰的に走査し、各配列内で _insert_before / _insert_after を反映する
     */
    public function reorderAllConfig(): void
    {
        // 現在の全コンフィグを取得
        $allConfig = config()->all();

        // 再配置処理を実行（すでに再帰的に処理される）
        $reorderedConfig = $this->reorderConfigArray($allConfig);

        // 各トップレベルのキーごとに更新
        foreach ($reorderedConfig as $key => $value) {
            config([$key => $value]);
        }
    }

    /**
     * 指定キーの前に新しい要素を挿入する
     */
    public function arrayInsertBeforeKey(array $array, string $targetKey, string $newKey, $newValue): array
    {
        $new = [];
        $inserted = false;
        foreach ($array as $k => $v) {
            if ($k === $targetKey) {
                $new[$newKey] = $newValue;
                $inserted = true;
            }
            $new[$k] = $v;
        }
        if (! $inserted) {
            $new[$newKey] = $newValue;
        }

        return $new;
    }

    /**
     * 指定キーの後に新しい要素を挿入する
     */
    public function arrayInsertAfterKey(array $array, string $targetKey, string $newKey, $newValue): array
    {
        $new = [];
        $inserted = false;
        foreach ($array as $k => $v) {
            $new[$k] = $v;
            if ($k === $targetKey) {
                $new[$newKey] = $newValue;
                $inserted = true;
            }
        }
        if (! $inserted) {
            $new[$newKey] = $newValue;
        }

        return $new;
    }

    /**
     * 配列を再帰的に走査し、各配列内で _insert_before / _insert_after の指定に従って並び替える
     *
     * @param  array  $config  再配置対象の配列
     * @return array 再配置後の配列
     */
    public function reorderConfigArray(array $config): array
    {
        // まず、配下の配列に対して再帰処理
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->reorderConfigArray($value);
            }
        }

        // 現在のレベルの配列で、_insert_before/_insert_after があれば再配置
        $reordered = $config;
        foreach ($reordered as $key => $value) {
            if (is_array($value) && (isset($value['_insert_before']) || isset($value['_insert_after']))) {
                if (isset($value['_insert_before'])) {
                    $target = $value['_insert_before'];
                    unset($value['_insert_before']);
                    unset($reordered[$key]);
                    $reordered = $this->arrayInsertBeforeKey($reordered, $target, $key, $value);
                } elseif (isset($value['_insert_after'])) {
                    $target = $value['_insert_after'];
                    unset($value['_insert_after']);
                    unset($reordered[$key]);
                    $reordered = $this->arrayInsertAfterKey($reordered, $target, $key, $value);
                }
            }
        }

        return $reordered;
    }
}
