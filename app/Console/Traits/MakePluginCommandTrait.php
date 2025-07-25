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
     * プラグインコマンドの共通引数を取得します
     *
     * @param bool $includeScope スコープ引数を含めるかどうか
     * @return string 共通引数のシグネチャ文字列
     */
    protected function getPluginCommandSignature(bool $includeScope = false): string
    {
        $signature = "{className? : The class name (e.g. User)} {pluginName? : The plugin name (e.g. MyPlugin)}";
        
        if ($includeScope) {
            $signature .= " {scope?}";
        }
        
        return $signature;
    }

    /**
     * プラグインファイルの生成処理を実行します
     *
     * @param string|null $classPath クラスパス（オプション）
     * @param string|null $pluginName プラグイン名（オプション）
     * @param string $fileType ファイルタイプ（'plugin' または 'custom'）
     * @param bool $needsScope スコープの選択が必要かどうか
     * @param string|null $scope 事前に指定されたスコープ（オプション）
     * @param array $options 追加オプション
     * @return bool 成功したかどうか
     */
    protected function generatePluginFile(
        ?string $classPath = null,
        ?string $pluginName = null,
        string $category = null,
        array $options = [],
        bool $needsScope = false,
        ?string $scope = null,
    ): bool {

        $fileType = 'plugin';

        // クラス名が指定されていない場合は入力を求める
        if (empty($classPath)) {
            $classPath = $this->askForClassName();
            if ($classPath === false) {
                return false;
            }
        }

        // プラグイン名の指定がなければプラグイン選択
        if (empty($pluginName)) {
            $pluginName = $this->choosePlugin();
            if (empty($pluginName)) {
                $this->error(__('command.plugin.not_found'));
                return false;
            }
        }
        $pluginName = Str::studly($pluginName);
        $licenseInfo = $this->getPluginLicenseInfo($pluginName) ?? [];

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
            'licenseInfo' => $licenseInfo,
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
            $licenseInfo
        );

        return true;
    }
}
