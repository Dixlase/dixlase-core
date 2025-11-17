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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Componentファイル作成用トレイト
 */
trait MakeComponentTrait
{
    use MakeFileTrait;
    
    /**
     * Component固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--inline : Create a component that renders an inline view}',
            '{--view : Create an anonymous component with only a view}',
            '{--path= : The location where the component view should be created}',
            '{--test : Generate an accompanying test for the Component}',
            '{--pest : Generate an accompanying Pest test for the Component}',
            '{--phpunit : Generate an accompanying PHPUnit test for the Component}',
        ];
    }
    
    /**
     * Componentを作成するメイン処理。
     *
     * @param  string  $className  クラス名
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '')
    {
        // --viewオプション: 匿名コンポーネント（ビューのみ）
        if (!empty($options['view'])) {
            return $this->createAnonymousComponent($className, $fileType, $options, $subDirs, $pluginName);
        }
        
        // 通常のコンポーネントクラスを作成
        $stub = $this->renderStub($options);
        
        // ビュー名をケバブケースに変換
        $viewName = Str::kebab($className);
        if (!empty($subDirs)) {
            $viewName = implode('.', array_map([Str::class, 'kebab'], $subDirs)) . '.' . $viewName;
        }
        
        $placeholders = [
            'view' => $viewName,
        ];
        
        $result = $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'component',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        // ビューファイルを作成（--inlineでない場合）
        if (empty($options['inline'])) {
            $this->createComponentView($className, $fileType, $options, $subDirs, $pluginName);
        }
        
        // テストファイルの生成
        if (!empty($options['test']) || !empty($options['pest']) || !empty($options['phpunit'])) {
            $this->createMatchingTest($className, $fileType, $options, $pluginName);
        }
        
        return $result;
    }

    /**
     * Component用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --inline オプションによってスタブを切り替え
        $stubName = (!empty($options['inline'])) ? 'component.inline.stub' : 'component.stub';
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }

    /**
     * 匿名コンポーネント（ビューのみ）を作成
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return bool
     */
    protected function createAnonymousComponent(string $className, string $fileType, array $options, array $subDirs, string $pluginName): bool
    {
        // ビューファイルのみを作成
        $this->createComponentView($className, $fileType, $options, $subDirs, $pluginName);
        
        $viewPath = $this->getComponentViewPath($className, $fileType, $options, $subDirs, $pluginName);
        $this->info("Anonymous component view created: {$viewPath}");
        
        return true;
    }

    /**
     * コンポーネント用のビューファイルを作成
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return void
     */
    protected function createComponentView(string $className, string $fileType, array $options, array $subDirs, string $pluginName): void
    {
        $viewPath = $this->getComponentViewPath($className, $fileType, $options, $subDirs, $pluginName);
        
        // ディレクトリを作成
        $viewDir = dirname($viewPath);
        if (!File::isDirectory($viewDir)) {
            File::makeDirectory($viewDir, 0755, true);
        }
        
        // 既存チェック
        if (File::exists($viewPath) && empty($options['force'])) {
            $this->warn("View already exists: {$viewPath}");
            return;
        }
        
        // ビューのスタブを取得
        $stubPath = config('command.custom_stub_directory') . '/component-view.stub';
        if (File::exists($stubPath)) {
            $content = File::get($stubPath);
        } else {
            // デフォルトのビューコンテンツ
            $content = "<div>\n    <!-- Component: " . Str::kebab($className) . " -->\n</div>\n";
        }
        
        File::put($viewPath, $content);
        $this->info("Component view created: {$viewPath}");
    }

    /**
     * コンポーネントビューのパスを取得
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return string
     */
    protected function getComponentViewPath(string $className, string $fileType, array $options, array $subDirs, string $pluginName): string
    {
        // --pathオプションが指定されている場合
        if (!empty($options['path'])) {
            $basePath = $options['path'];
        } else {
            // デフォルトのビューパス
            if ($fileType === 'plugin') {
                $basePath = base_path("plugins/{$pluginName}/resources/views/components");
            } elseif ($fileType === 'custom_plugin') {
                $basePath = base_path("custom/plugins/{$pluginName}/resources/views/components");
            } elseif ($fileType === 'core' && empty($pluginName)) {
                // カスタムコアの場合
                $basePath = base_path("custom/resources/views/components");
            } else {
                // 通常のコア
                $basePath = resource_path('views/components');
            }
        }
        
        // サブディレクトリを追加
        if (!empty($subDirs)) {
            $basePath .= '/' . implode('/', array_map('strtolower', $subDirs));
        }
        
        // ファイル名をケバブケースに変換
        $fileName = Str::kebab($className) . '.blade.php';
        
        return $basePath . '/' . $fileName;
    }

    /**
     * Componentに対応するテストファイルを生成
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  string  $pluginName
     * @return void
     */
    protected function createMatchingTest(string $className, string $fileType, array $options, string $pluginName): void
    {
        $testClassName = $className . 'Test';
        
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
    }
}
