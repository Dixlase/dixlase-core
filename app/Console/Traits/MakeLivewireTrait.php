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
 * Livewireファイル作成用トレイト
 */
trait MakeLivewireTrait
{
    use MakeFileTrait;
    
    /**
     * Livewire固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--inline : Generate an inline Livewire component}',
            '{--test : Generate an accompanying Test test for the Livewire component}',
            '{--pest : Generate an accompanying Pest test for the Livewire component}',
            '{--stub= : Specify a custom stub file to use}',
        ];
    }
    
    /**
     * Livewireを作成するメイン処理。
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
        // スタブの取得
        $stub = $this->renderStub($options);
        
        // プレースホルダーの準備
        $placeholders = [
            'view' => $this->getLivewireViewName($className, $subDirs),
        ];
        
        // ファイル生成
        $result = $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'livewire',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );

        // ビューファイルの作成（inlineでない場合）
        if (!($options['inline'] ?? false)) {
            $this->createLivewireView($className, $fileType, $subDirs, $pluginName);
        }

        // テスト生成
        if ($options['test'] ?? false || $options['pest'] ?? false) {
            $this->createTest($className, $fileType, $subDirs, $pluginName, $options);
        }

        return $result;
    }

    /**
     * Livewire用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // カスタムスタブが指定されている場合
        if (!empty($options['stub'])) {
            $stubPath = base_path($options['stub']);
            if (File::exists($stubPath)) {
                return File::get($stubPath);
            }
        }

        // inline オプションによってスタブを切り替え
        $stubName = ($options['inline'] ?? false) ? 'livewire.inline.stub' : 'livewire.stub';
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }

    /**
     * Livewireのビュー名を取得
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @return string
     */
    protected function getLivewireViewName($className, $subDirs = []): string
    {
        $parts = array_merge($subDirs, [Str::kebab($className)]);
        return 'livewire.' . implode('.', array_map(fn($part) => Str::kebab($part), $parts));
    }

    /**
     * Livewireのビューファイルを作成
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return void
     */
    protected function createLivewireView($className, $fileType, $subDirs, $pluginName = '')
    {
        $viewPath = $this->getLivewireViewPath($className, $fileType, $subDirs, $pluginName);
        $viewDir = dirname($viewPath);

        // ディレクトリ作成
        if (!File::exists($viewDir)) {
            File::makeDirectory($viewDir, 0755, true);
        }

        // ビューファイルの内容
        $viewContent = '<div>
    {{-- Livewire Component: ' . $className . ' --}}
</div>
';

        // ファイル書き込み
        File::put($viewPath, $viewContent);
        $this->info("ビューファイル [ {$viewPath} ] を作成しました。");
    }

    /**
     * Livewireのビューファイルパスを取得
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return string
     */
    protected function getLivewireViewPath($className, $fileType, $subDirs, $pluginName = '')
    {
        $basePath = match($fileType) {
            'plugin' => base_path("plugins/{$pluginName}/resources/views"),
            'custom_plugin' => base_path("custom/plugins/{$pluginName}/resources/views"),
            default => base_path('custom/resources/views'),
        };

        $parts = array_merge(['livewire'], $subDirs, [Str::kebab($className)]);
        return $basePath . '/' . implode('/', array_map(fn($part) => Str::kebab($part), $parts)) . '.blade.php';
    }

    /**
     * Create a test for the Livewire component
     */
    protected function createTest($className, $fileType, $subDirs, $pluginName = '', $options = [])
    {
        $testName = $className . 'Test';
        
        if ($fileType === 'plugin') {
            $this->call('make:plugin:test', [
                'className' => $testName,
                'pluginName' => $pluginName,
                '--pest' => $options['pest'] ?? false,
            ]);
        } else {
            $this->call('make:custom:test', [
                'className' => $testName,
                'fileType' => 'core',
                'pluginName' => null,
                '--pest' => $options['pest'] ?? false,
                '--no-interaction' => true,
            ]);
        }
    }
}
