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
    use MakeThemeCommandTrait;

    /**
     * カスタムコマンドの共通引数を取得します
     *
     * @return string 共通引数のシグネチャ文字列
     */
    protected function getCustomCommandSignature(bool $includeScope = false): string
    {
        $signature = "{className? : The class name (e.g. User)} {fileType? : The file type (e.g. Service, Repository)} {targetName? : The target name (e.g. MyPlugin)}";
        
        if ($includeScope) {
            $signature .= " {scope?}";
        }
        
        return $signature;
    }

    /**
     * カスタムファイルを生成する共通処理
     *
     * @param string|null $classPath クラスパス（例: Admin/UserController）
     * @param string|null $fileType ファイルタイプ（core, plugin, theme）
     * @param string|null $targetName プラグイン名またはテーマ名
     * @param string|null $category ファイルカテゴリ（controller, model, service など）
     * @param array $options 追加オプション
     * @param bool $needsScope スコープが必要かどうか
     * @param string|null $scope スコープ（admin, front, plain）
     * @return bool 成功した場合true
     */
    protected function generateCustomFile(
        ?string $classPath = null,
        ?string $fileType = null,
        ?string $targetName = null,
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
            [$fileType, $targetName] = $this->chooseFileType();
            if (!$fileType) {
                return false;
            }
        }

        // プラグイン名やテーマ名はchooseFileType()内で既に選択されているため、
        // ここでの追加処理は通常不要（引数で直接指定された場合のフォールバック）

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

        // カスタムコマンドでpluginが指定された場合はcustom_pluginに変換
        if ($fileType === 'plugin') {
            $fileType = 'custom_plugin';
        }

        // カスタムコマンドでthemeが指定された場合はcustom_themeに変換
        if ($fileType === 'theme') {
            $fileType = 'custom_theme';
        }

        // 対象名がnullの場合は空文字列に変換（coreの場合など）
        $targetName = $targetName ?? '';

        // ライセンス情報を取得
        $licenseInfo = $this->getCustomFileLicenseInfo($fileType, $targetName);

        // 共通パラメータを準備
        $common = [
            'fileType' => $fileType,
            'className' => $className,
            'targetName' => $targetName,
            'pluginName' => $targetName,  // 互換性のため（既存コードがこのキーを参照している可能性）
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
            $targetName,
            $licenseInfo
        );

        return true;
    }

    /**
     * カスタムディレクトリのライセンス情報を取得
     *
     * @param string $fileType ファイルタイプ（core、custom_plugin、custom_theme）
     * @param string|null $targetName プラグイン名またはテーマ名
     * @param bool $showNotice 警告メッセージを表示するかどうか
     * @return array|null
     */
    protected function getCustomFileLicenseInfo(string $fileType = 'core', ?string $targetName = null, bool $showNotice = false): array
    {
        // ファイルタイプに応じてライセンス情報を取得
        if ($fileType === 'core') {
            // コアの場合は dixlase.json から取得（getLicenseInfoFromJson内で処理）
            return $this->getLicenseInfoFromJson('custom', null, $showNotice) ?? [];
        } elseif ($fileType === 'custom_plugin' && $targetName) {
            // プラグイン用カスタムファイルの場合はプラグインのライセンス情報を使用
            return $this->getLicenseInfoFromJson('plugins', $targetName, $showNotice) ?? [];
        } elseif ($fileType === 'custom_theme' && $targetName) {
            // テーマ用カスタムファイルの場合はテーマのライセンス情報を使用
            return $this->getLicenseInfoFromJson('themes', $targetName, $showNotice) ?? [];
        }
        
        // デフォルトはcustomディレクトリ
        return $this->getLicenseInfoFromJson('custom', null, $showNotice) ?? [];
    }
}
