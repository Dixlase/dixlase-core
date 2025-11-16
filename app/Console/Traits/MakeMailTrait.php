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

/**
 * Mailファイル作成用トレイト
 */
trait MakeMailTrait
{
    use MakeFileTrait;
    
    /**
     * Mail固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--m|markdown : Create a new Markdown template for the mailable}',
            '{--view : Create a new Blade template for the mailable}',
            '{--test : Generate an accompanying test for the Mailable}',
            '{--pest : Generate an accompanying Pest test for the Mailable}',
            '{--phpunit : Generate an accompanying PHPUnit test for the Mailable}',
        ];
    }
    
    /**
     * Mailクラスを作成するメイン処理。
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
        $placeholders = [];
        
        // --markdownまたは--viewオプションが指定されている場合、viewプレースホルダーを追加
        if (!empty($options['markdown'])) {
            $viewPath = $options['markdown'];
            // 値が指定されていない場合（-m のみ）はデフォルトのビュー名を生成
            if ($viewPath === true || empty($viewPath)) {
                $viewPath = 'mail.' . \Illuminate\Support\Str::kebab($className);
            }
            $placeholders['view'] = $viewPath;
        } elseif (!empty($options['view'])) {
            $viewPath = $options['view'];
            // 値が指定されていない場合（--view のみ）はデフォルトのビュー名を生成
            if ($viewPath === true || empty($viewPath)) {
                $viewPath = 'mail.' . \Illuminate\Support\Str::kebab($className);
            }
            $placeholders['view'] = $viewPath;
        }
        
        // ファイル生成
        $result = $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'mail',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        // テストファイルの生成
        if (!empty($options['test']) || !empty($options['pest']) || !empty($options['phpunit'])) {
            $this->createMatchingTest($className, $fileType, $options, $pluginName);
        }
        
        return $result;
    }

    /**
     * Mail用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --markdown または --view オプションによってスタブを切り替え
        if (!empty($options['markdown'])) {
            $stubName = 'markdown-mail.stub';
        } elseif (!empty($options['view'])) {
            $stubName = 'view-mail.stub';
        } else {
            $stubName = 'mail.stub';
        }
        
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
    
    /**
     * Mailableに対応するテストファイルを生成
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
