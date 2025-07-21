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
     * Bladeファイルを作成
     *
     * @param array $common 共通初期化パラメータ
     * @param array $options オプション
     * @return bool 成功したかどうか
     */


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


        $this->info(print_r($subDirs));
        

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


    

        /*
        // 1) スコープを処理
        $scope = $this->handleScopeSelection($options);
        if ($scope === false) {
            return false;
        }
        
        // 2) スコープに基づいてサブディレクトリを設定
        if ($scope !== 'plain') {
            $subDirs[] = $scope;
        }

        // 3) 余分な拡張子を取り除く
        $viewName = $this->normalizeViewName($className);

        // 4) サブディレクトリとファイル名を切り分ける
        $parts = $this->splitViewPath($viewName);
        $fileName = $this->formatFileName(array_pop($parts));
        
        // 5) サブディレクトリをマージ（オプションで指定されたものを優先）
        $subDirs = array_merge($subDirs, $parts);

        // 6) スタブファイルを解決
        $stubFile = $this->resolveStubFile(['scope' => $scope] + $options);
        
        // 7) プレースホルダーを準備
        $extraPlaceholders = $this->preparePlaceholders($viewName, ['scope' => $scope] + $options);

        // 8) Bladeファイルを作成
        $this->makeFilerBlade($fileName, $subDirs, ['scope' => $scope] + $options, $stubFile, $extraPlaceholders);
        
        return true;
        */
    }
    
    /**
     * スコープの選択を処理する
     *
     * @param array $options コマンドオプション
     * @return string|false 選択されたスコープ、またはエラー時はfalse
     */
    protected function handleScopeSelection(array $options)
    {
        $scope = $options['scope'] ?? null;
        
        // スコープが指定されていない場合は選択を求める
        if (empty($scope)) {
            $scope = $this->choice(
                'Select the scope for the blade file',
                ['plain', 'front', 'admin'],
                0
            );
        }
        
        // 有効なスコープか検証
        if (!in_array($scope, ['plain', 'front', 'admin'])) {
            $this->error("Invalid scope: '{$scope}'. Choose 'plain', 'front' or 'admin'.");
            return false;
        }
        
        return $scope;
    }
    
    /**
     * ビュー名を正規化する
     */
    protected function normalizeViewName(string $viewName): string
    {
        // 余分な拡張子を取り除く
        if (str_ends_with($viewName, '.php')) {
            $viewName = substr($viewName, 0, -4);
        }
        if (str_ends_with($viewName, '.blade')) {
            $viewName = substr($viewName, 0, -6);
        }
        
        return $viewName;
    }
    
    /**
     * ビューパスを分割する
     */
    protected function splitViewPath(string $viewPath): array
    {
        return array_filter(explode('/', $viewPath), 'strlen');
    }
    
    /**
     * ファイル名をフォーマットする
     */
    protected function formatFileName(string $name): string
    {
        return Str::kebab($name) . '.blade.php';
    }
    
    /**
     * プレースホルダーを準備する
     */
    protected function preparePlaceholders(string $viewName, array $options): array
    {
        return [
            '{{ license }}'  => $this->fileGenerator->getLicenseForBlade(),
            '{{ filename }}' => $viewName,
            '{{ scope }}'    => $options['scope'] ?? 'plain',
        ];
    }

    /**
     * Blade専用に実際のファイルを作成
     */
    /**
     * Blade専用に実際のファイルを作成
     */
    protected function makeFilerBlade(
        string $fileName,
        array $subDirs,
        array $options,
        string $stubFile,
        array $extraPlaceholders
    ): void {
        // (A) サブディレクトリを /resources/views/admin/media のように組み立て
        $targetDirectory = $this->getDirectory($subDirs);
        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        // (B) 最終的なファイルパス
        $filePath = rtrim($targetDirectory, '/') . '/' . $fileName;

        // (C) --force オプション
        $force = $options['force'] ?? false;
        if (! $force) {
            $this->fileGenerator->prepareFilePath($filePath, "[{$fileName}] already exists.");
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // (D) スタブ読み込み
        $stubContent = $this->loadStubFile($stubFile);

        // (E) ライセンスなどを埋め込み
        $finalContent = $this->fileGenerator->embedLicenseBlade($stubContent, $extraPlaceholders);

        // (F) 実ファイル作成
        $this->fileGenerator->generateFile($filePath, $finalContent);

        $this->info("Blade file [{$filePath}] created successfully.");
    }

    /**
     * Blade用: サブディレクトリを結合したディレクトリパスを返す
     */
    protected function getDirectory(array $subDirs): string
    {
        $basePath = resource_path('views');
        if (! empty($subDirs)) {
            $basePath .= '/' . implode('/', $subDirs);
        }
        return $basePath;
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

    /**
     * スタブファイルを読み込み (親 trait のメソッドを独自に定義・上書き)
     */
    protected function loadStubFile(string $stubFile): string
    {
        $customStubPaths = [base_path('stubs/custom')];
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFile}");

        return $this->fileGenerator->getStubContent($stubFile, $defaultStubPath, $customStubPaths);
    }
}
