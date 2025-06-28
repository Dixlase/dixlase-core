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
