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
 * Voltコンポーネントファイル作成用トレイト
 */
trait MakeVoltTrait
{
    use MakeFileTrait;
    
    /**
     * Volt固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--class : Create a class based component}',
            '{--functional : Create a functional component}',
            '{--test : Generate an accompanying test for the Volt component}',
            '{--pest : Generate an accompanying Pest test for the Volt component}',
            '{--phpunit : Generate an accompanying PHPUnit test for the Volt component}',
        ];
    }
    
    /**
     * Voltコンポーネントを作成するメイン処理。
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
        // Voltファイルのパスを取得
        $voltPath = $this->getVoltPath($className, $fileType, $subDirs, $pluginName);
        
        // ディレクトリを作成
        $voltDir = dirname($voltPath);
        if (!File::isDirectory($voltDir)) {
            File::makeDirectory($voltDir, 0755, true);
        }
        
        // 既存チェック
        if (File::exists($voltPath) && empty($options['force'])) {
            $this->warn("Volt component already exists: {$voltPath}");
            return false;
        }
        
        // スタブの取得
        $stub = $this->renderStub($options);
        
        // ライセンス情報を取得
        $licenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);
        
        // ライセンスヘッダーを生成
        $license = '';
        if (isset($licenseInfo['template']) && !empty($licenseInfo['template'])) {
            $license = $this->replacePlaceholders($licenseInfo['template'], $licenseInfo['info'] ?? []);
            // Blade用の整形
            $license = $this->embedLicenseForBlade($license);
        }
        
        // プレースホルダーを置換
        $content = str_replace('{{ license }}', $license, $stub);
        $content = str_replace('{{ component }}', Str::kebab($className), $content);
        
        // ファイルを作成
        File::put($voltPath, $content);
        $this->info("Volt component created: {$voltPath}");
        
        // テストファイルの生成
        if (!empty($options['test']) || !empty($options['pest']) || !empty($options['phpunit'])) {
            $this->createMatchingTest($className, $fileType, $options, $pluginName);
        }
        
        return true;
    }

    /**
     * Volt用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --class と --functional オプションによってスタブを切り替え
        if (!empty($options['class'])) {
            $stubName = 'volt-class.stub';
        } elseif (!empty($options['functional'])) {
            $stubName = 'volt-functional.stub';
        } else {
            // デフォルトは functional
            $stubName = 'volt-functional.stub';
        }
        
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }

    /**
     * Voltコンポーネントのパスを取得
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return string
     */
    protected function getVoltPath(string $className, string $fileType, array $subDirs, string $pluginName): string
    {
        // ベースパスを決定
        if ($fileType === 'plugin') {
            $basePath = base_path("plugins/{$pluginName}/resources/views/livewire");
        } elseif ($fileType === 'custom_plugin') {
            $basePath = base_path("custom/plugins/{$pluginName}/resources/views/livewire");
        } elseif ($fileType === 'core' && empty($pluginName)) {
            // カスタムコアの場合
            $basePath = base_path("custom/resources/views/livewire");
        } else {
            // 通常のコア
            $basePath = resource_path('views/livewire');
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
     * Voltコンポーネントに対応するテストファイルを生成
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
