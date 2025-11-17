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
 * Viewファイル作成用トレイト
 */
trait MakeViewTrait
{
    /**
     * View固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--extension= : The extension of the generated view}',
            '{--test : Generate an accompanying test for the View}',
            '{--pest : Generate an accompanying Pest test for the View}',
            '{--phpunit : Generate an accompanying PHPUnit test for the View}',
        ];
    }

    /**
     * Viewファイルを作成するメイン処理。
     *
     * @param  string  $className  クラス名
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @return bool
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName
    ){
        // スコープの取得（Dixlase独自機能）
        $scope = $options['scope'] ?? 'plain';
        
        // スタブの取得（スコープを考慮）
        $stub = $this->renderStub($options);

        // Viewファイルの場合はサブディレクトリをすべて小文字に変換
        $subDirs = array_map('strtolower', $subDirs);

        // 拡張子の取得（デフォルトは blade.php）
        // オプションに拡張子を追加して makeFiler に渡す
        $options['extension'] = $options['extension'] ?? 'blade.php';

        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'view',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        // テストファイルの生成
        if (!empty($options['test']) || !empty($options['pest']) || !empty($options['phpunit'])) {
            $this->createMatchingTest($className, $fileType, $options, $pluginName, $subDirs);
        }
        
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
        // スコープの取得（Dixlase独自機能）
        $scope = $options['scope'] ?? 'plain';
        
        // スコープに応じたスタブファイル名を設定
        $stubName = 'view.stub';
        if ($scope === 'admin') {
            $stubName = 'blade-admin.stub';
        } elseif ($scope === 'front') {
            $stubName = 'blade-front.stub';
        }

        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }

    /**
     * Viewに対応するテストファイルを生成
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  string  $pluginName
     * @param  array   $subDirs
     * @return void
     */
    protected function createMatchingTest(string $className, string $fileType, array $options, string $pluginName, array $subDirs): void
    {
        // ビュー名を生成（ドット記法）
        $viewName = strtolower($className);
        if (!empty($subDirs)) {
            $viewName = implode('.', array_map('strtolower', $subDirs)) . '.' . strtolower($className);
        }
        
        $testClassName = Str::studly($className) . 'ViewTest';
        
        // テストオプションを準備
        $testOptions = [];
        
        // Pestオプションを引き継ぐ
        if (!empty($options['pest'])) {
            $testOptions['--pest'] = true;
        } elseif (!empty($options['phpunit'])) {
            $testOptions['--phpunit'] = true;
        }
        
        // Artisanコマンドを呼び出す
        if ($fileType === 'plugin') {
            $this->call('make:plugin:test', array_merge([
                'className' => $testClassName,
                'pluginName' => $pluginName,
            ], $testOptions));
        } else {
            // core または custom_plugin
            $this->call('make:custom:test', array_merge([
                'className' => $testClassName,
                'fileType' => $fileType === 'core' ? 'core' : 'plugin',
                'pluginName' => $fileType === 'custom_plugin' ? $pluginName : null,
            ], $testOptions));
        }
        
        $this->info("View test created for view: {$viewName}");
    }
}
