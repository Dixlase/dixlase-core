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

        // スコープの選択
        $scope = $this->chooseScope();

        // パス情報の分解
        [$className, $subDirs] = $this->parseClassPath($classPath, $scope);

        // ライセンス取得
        $licenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);

        return [
            'fileType' => $fileType,
            'pluginName' => $pluginName,
            'scope' => $scope,
            'className' => $className,
            'subDirs' => $subDirs,
            'licenseInfo' => $licenseInfo
        ];
    }

    /**
     * カスタムファイルの生成処理
     *
     * @param array $commonInit initializeCustomCommandの結果
     * @param array $options オプション
     * @return int 成功したかどうか
     */
    protected function makeCustomFile(array $commonInit, array $options): int
    {
        // 連想配列から値を取得
        $fileType = $commonInit['fileType'];
        $pluginName = $commonInit['pluginName'];
        $scope = $commonInit['scope'];
        $className = $commonInit['className'];
        $subDirs = $commonInit['subDirs'];
        $licenseInfo = $commonInit['licenseInfo'];

        // オプションにスコープを追加
        $options = array_merge($options, ['scope' => $scope]);

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
