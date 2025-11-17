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
 * Listenerファイル作成用トレイト
 */
trait MakeListenerTrait
{
    use MakeFileTrait;
    
    /**
     * Listener固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--e|event= : The event class being listened for}',
            '{--queued : Indicates the event listener should be queued}',
            '{--test : Generate an accompanying test for the Listener}',
            '{--pest : Generate an accompanying Pest test for the Listener}',
            '{--phpunit : Generate an accompanying PHPUnit test for the Listener}',
        ];
    }
    
    /**
     * Listenerクラスを作成するメイン処理。
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
        $placeholders = $this->preparePlaceholders($className, $fileType, $options, $pluginName);
        
        // ファイル生成
        $result = $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'listener',
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
     * Listener用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --eventと--queuedオプションによってスタブを切り替え
        $hasEvent = !empty($options['event']);
        $isQueued = !empty($options['queued']);
        
        if ($hasEvent && $isQueued) {
            $stubName = 'listener.typed.queued.stub';
        } elseif ($hasEvent) {
            $stubName = 'listener.typed.stub';
        } elseif ($isQueued) {
            $stubName = 'listener.queued.stub';
        } else {
            $stubName = 'listener.stub';
        }
        
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
    
    /**
     * プレースホルダーを準備
     *
     * @param  string  $className
     * @param  string  $fileType
     * @param  array   $options
     * @param  string  $pluginName
     * @return array
     */
    protected function preparePlaceholders(string $className, string $fileType, array $options, string $pluginName): array
    {
        $placeholders = [];
        
        // --eventオプションが指定されている場合
        if (!empty($options['event'])) {
            $eventFqcn = $this->qualifyEvent($options['event'], $fileType, $pluginName);
            $eventBase = class_basename($eventFqcn);
            
            $placeholders['event'] = $eventBase;
            $placeholders['eventNamespace'] = $eventFqcn;
        }
        
        return $placeholders;
    }
    
    /**
     * Eventの完全修飾クラス名を取得
     *
     * @param  string  $eventOption
     * @param  string  $fileType
     * @param  string  $pluginName
     * @return string
     */
    protected function qualifyEvent(string $eventOption, string $fileType, string $pluginName): string
    {
        // すでに名前空間が含まれている場合はそのまま使用
        if (str_contains($eventOption, '\\')) {
            return ltrim($eventOption, '\\');
        }
        
        // ファイルタイプに応じて名前空間を決定
        if ($fileType === 'plugin') {
            return "Plugins\\{$pluginName}\\App\\Events\\{$eventOption}";
        } elseif ($fileType === 'custom_plugin') {
            return "Custom\\Plugins\\{$pluginName}\\App\\Events\\{$eventOption}";
        } else {
            // core または custom_core
            return "Custom\\App\\Events\\{$eventOption}";
        }
    }
    
    /**
     * Listenerに対応するテストファイルを生成
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
