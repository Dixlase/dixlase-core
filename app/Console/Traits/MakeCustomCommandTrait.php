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

namespace App\Console\Traits;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * カスタムディレクトリ用ファイル作成コマンドの共通機能を提供するトレイト
 */
trait MakeCustomCommandTrait
{
    use MakeFileTrait;

    /**
     * カスタムコマンドの共通引数を取得します
     *
     * @return string 共通引数のシグネチャ文字列
     */
    protected function getCustomCommandSignature(bool $includeScope = false): string
    {
        $signature = "{className? : The class name (e.g. User)} {fileType? : The file type (e.g. Service, Repository)} {pluginName? : The plugin name (e.g. MyPlugin)}";
        
        if ($includeScope) {
            $signature .= " {scope?}";
        }
        
        return $signature;
    }


    protected function generateCustomFile(
        ?string $classPath = null,
        ?string $fileType = null,
        ?string $pluginName = null,
        string $category = null,
        array $options = [],
        bool $needsScope = false,
        ?string $scope = null,
    ): bool {

        // クラス名が指定されていない場合は入力を求める
        if (empty($classPath)) {
            $classPath = $this->askForClassName();
            if ($classPath === false) {
                return false;
            }
        }
        
        // ファイルタイプの選択
        if (empty($fileType)) {
            [$fileType, $pluginName] = $this->chooseFileType();
            if (!$fileType) {
                return false;
            }
        }

        // スコープの処理（必要な場合）
        if ($needsScope && empty($scope)) {
            $scope = $this->chooseScope();
            if ($scope === false) {
                return false;
            }
        }

        // パス情報の分解（スコープがある場合は考慮）
        [$className, $subDirs] = $this->parseClassPath(
            $category,
            $classPath,
            $needsScope ? $scope : null
        );

        // 共通パラメータを準備
        $common = [
            'fileType' => $fileType,
            'className' => $className,
            'pluginName' => $pluginName,
            'subDirs' => $subDirs,
            'scope' => $scope,
            'category' => $category
        ];
        
        // カテゴリに基づいてオプションをマージ
        $options = $this->mergeCategoryOptions($category, $common, $options);

        // スコープの指定があれば、オプションにマージ
        if (!empty($scope)) {
            $options = array_merge($options, ['scope' => $scope]);
        }

        // ファイル生成
        $this->makeFile(
            $className,
            $fileType,
            $options,
            $subDirs,
            $pluginName,
        );

        return true;
    }
}
