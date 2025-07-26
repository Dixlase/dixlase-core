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

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


/**
 * Bladeファイルを作成するためのTrait。
 * -> いわゆる「クラス + namespace」が存在しないため、MakeFileTraitは使わず、
 *    シンプルに「フォルダ+ファイル」を生成するだけに特化する。
 */
trait MakeBladeTrait
{

    /**
     * コンフィグファイル作成の共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [

        ];
    }

    /**
     * Bladeファイルを作成するメイン処理。
     *
     * @param  string  $className  クラス名
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @param  array   $licenseInfo ライセンス情報
     * @return bool
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName
    ){

        $scope = $options['scope'] ?? 'plain'; // スコープの取得（例: admin, front, plain）
        
        // スタブの取得（スコープを考慮）
        $stub = $this->renderStub($options, $scope);

        // Bladeファイルの場合はサブディレクトリをすべて小文字に変換
        $subDirs = array_map('strtolower', $subDirs);

        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'blade',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        return true;
    }
    

    /**
     * スコープに基づいて適切なスタブファイルをレンダリングする
     * 
     * @param array $options オプション
     * @return string スタブファイルの内容
     */
    protected function renderStub(array $options = []): string
    {
        $scope = $options['scope'] ?? 'plain';
        
        // スコープに応じたスタブファイル名を設定
        $stubName = 'blade.stub';
        if ($scope === 'admin') {
            $stubName = 'blade-admin.stub';
        } elseif ($scope === 'front') {
            $stubName = 'blade-front.stub';
        }

        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }
}
