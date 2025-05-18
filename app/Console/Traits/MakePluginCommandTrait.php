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
 * プラグイン用ファイル作成コマンドの共通機能を提供するトレイト
 */
trait MakePluginCommandTrait
{
    use MakeFileTrait;
    use PluginManagementTrait;
    use MakeLicenseTrait;

    /**
     * プラグインコマンドの共通初期化処理
     *
     * @param string $classPath クラスパス
     * @return array|false [$fileType, $pluginName, $scope, $className, $subDirs, $licenseInfo] または false
     */
    protected function initializePluginCommand(string $classPath)
    {
        // プラグイン選択
        $pluginName = $this->choosePlugin();
        if (!$pluginName) {
            $this->error(__('command.plugin.not_found'));
            return false;
        }

        // スコープの選択
        $scope = $this->chooseScope();

        // パス情報の分解
        [$className, $subDirs] = $this->parseClassPath($classPath, $scope);
        $pluginName = Str::studly($pluginName);

        // ライセンス情報の取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName) ?? [];

        // fileTypeは常に'plugin'
        return ['plugin', $pluginName, $scope, $className, $subDirs, $licenseInfo];
    }

    /**
     * プラグインファイルの生成処理
     *
     * @param array $commonInit initializePluginCommandの結果
     * @param array $options オプション
     * @return bool 成功したかどうか
     */
    protected function makePluginFile(array $commonInit, array $options): bool
    {
        [$fileType, $pluginName, $scope, $className, $subDirs, $licenseInfo] = $commonInit;

        // オプションにスコープを追加
        $options = array_merge($options, ['scope' => $scope]);

        // ファイル生成
        $this->makeFile(
            $className,
            'plugin', // プラグイン用のファイルタイプ
            $options,
            $subDirs,
            $pluginName,
            $licenseInfo
        );

        return true;
    }
}
