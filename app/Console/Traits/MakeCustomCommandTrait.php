<?php

/**
 * This file is part of MySoftware.
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


    /**
     * カスタムディレクトリ用ファイル作成コマンドの共通初期化処理
     *
     * @param string $classPath クラスパス
     * @return array|false [$fileType, $pluginName, $scope, $className, $subDirs, $licenseInfo] または false
     */
    protected function initializeCustomCommand(string $classPath)
    {
        // ファイルタイプの選択
        [$fileType, $pluginName] = $this->chooseFileType();
        if (!$fileType) {
            return false;
        }

        // パス情報の分解（スコープなしで一度パース）
        [$className, $subDirs] = $this->parseClassPath($classPath, '');

        $common = [
            'fileType' => $fileType,
            'pluginName' => $pluginName,
            'className' => $className,
            'subDirs' => $subDirs,
        ];

        
        // コントローラーの場合のみスコープを選択
        
        if (in_array('App\\Console\\Traits\\MakeControllerTrait', class_uses_recursive($this))) {
            $scope = $this->chooseScope();
            // スコープが選択されたら再度パス情報を分解
            if ($scope) {
                [$className, $subDirs] = $this->parseClassPath($classPath, $scope);
                $common['className'] = $className;
                $common['subDirs'] = $subDirs;
                $common['scope'] = $scope;
            }
        }

        // ライセンス取得
        $common['licenseInfo'] = $this->getFileTypeLicenseInfo($fileType, $pluginName);

        return  $common;
    }


    /**
     * カスタムファイルの生成処理
     *
     * @param array $common initializeCustomCommandの結果
     * @param array $options オプション
     * @return bool 成功したかどうか
     */
    protected function makeCustomFile(array $common, array $options): bool
    {
        // 連想配列から値を取得
        $fileType = $common['fileType'];
        $pluginName = $common['pluginName'];
        $className = $common['className'];
        $subDirs = $common['subDirs'] ?? [];
        $licenseInfo = $common['licenseInfo'] ?? [];
        $category = $common['category'] ?? 'default';
        
        // カテゴリに基づいて適切なオプションを追加
        if ($category === 'route' && isset($common['routeType'])) {
            $options = array_merge($options, ['routeType' => $common['routeType']]);
        } elseif (isset($common['scope'])) {
            $options = array_merge($options, ['scope' => $common['scope']]);
        }

        // ファイルタイプを決定
        $targetFileType = $fileType === 'core' ? 'custom_core' : 'custom_plugin';


        // ファイル生成
        $this->makeFile(
            $className,
            $targetFileType,
            $options,
            $subDirs,
            $pluginName,
            $licenseInfo
        );

        return true;
    }
}
