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

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


/**
 * プロバイダー作成用トレイト
 * MakeModelTrait と同じパターンに従う
 */
trait MakeProviderTrait
{
    use MakeFileTrait;
    
    /**
     * プロバイダー固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [

        ];
    }
    
    /**
     * プロバイダーを作成するメイン処理。
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
        // プラグイン用のフラグを設定
        if (!empty($pluginName)) {
            $options['plugin'] = true;
        }
        
        // スタブの取得
        $stub = $this->renderStub($options);
        
        // プレースホルダーを準備
        $placeholders = [];
        if (!empty($pluginName)) {
            // プラグイン名からスラッグと設定ファイル名を生成
            $pluginSlug = Str::kebab($pluginName);
            $configFileName = Str::snake($pluginName);
            
            $placeholders = [
                'pluginSlug' => $pluginSlug,
                'configFileName' => $configFileName,
            ];
        }
                
        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'provider',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        return true;
    }

    /**
     * オプションに基づいて適切なスタブをレンダリングします。
     *
     * @param  string  $type
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // プロバイダー用のスタブを選択
        $stubName = 'provider.stub';
        if ($options['plugin'] ?? false) {
            $stubName = 'provider.plugin.stub';
        }

        // スタブファイルの内容を取得
        $stub = $this->getStubContent($stubName);

        return $stub;
    }
    

    /**
     * プロバイダーオプションを処理します。
     *
     * @param  string  $className
     * @param  array  $options
     * @param  string  $fileType
     * @param  array  $subDirs
     * @param  string  $pluginName
     * @return array
     */
    protected function handleOptions($className, $options, $fileType, $subDirs = [], $pluginName = '')
    {
        // プラグインオプションが有効な場合は、オプションに追加
        if (!empty($pluginName)) {
            $options['plugin'] = true;
        }
        
        // その他のオプション処理が必要な場合はここに実装
        
        return $options;
    }

}
