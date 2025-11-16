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
 * ポリシーファイル作成用トレイト
 */
trait MakePolicyTrait
{
    use MakeFileTrait;
    
    /**
     * Policy固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--m|model= : The model that the policy applies to}',
            '{--g|guard= : The guard that the policy relies on}',
        ];
    }
    
    /**
     * ポリシークラスを作成するメイン処理。
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
        return $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'policy',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
    }

    /**
     * Policy用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --model オプションによってスタブを切り替え
        $stubName = (!empty($options['model'])) ? 'policy.stub' : 'policy.plain.stub';
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
        
        // --modelオプションが指定されている場合
        if (!empty($options['model'])) {
            $modelFqcn = $this->qualifyModel($options['model'], $fileType, $pluginName);
            $modelBase = class_basename($modelFqcn);
            $modelVar  = Str::camel($modelBase);
            
            $placeholders['namespacedModel'] = $modelFqcn;
            $placeholders['model'] = $modelBase;
            $placeholders['modelVariable'] = $modelVar;
        }
        
        // User model
        $userFqcn = $this->qualifyUserModel($options);
        $userBase = class_basename($userFqcn);
        
        $placeholders['namespacedUserModel'] = $userFqcn;
        $placeholders['user'] = $userBase;
        
        return $placeholders;
    }

    /**
     * モデルの完全修飾クラス名を取得
     *
     * @param  string  $modelOption
     * @param  string  $fileType
     * @param  string  $pluginName
     * @return string
     */
    protected function qualifyModel(string $modelOption, string $fileType, string $pluginName): string
    {
        // すでに名前空間が含まれている場合はそのまま使用
        if (str_contains($modelOption, '\\')) {
            return ltrim($modelOption, '\\');
        }
        
        // ファイルタイプに応じて名前空間を決定
        if ($fileType === 'plugin') {
            return "Plugins\\{$pluginName}\\App\\Models\\{$modelOption}";
        } elseif ($fileType === 'custom_plugin') {
            return "Custom\\Plugins\\{$pluginName}\\App\\Models\\{$modelOption}";
        } else {
            // core または custom_core
            return "Custom\\App\\Models\\{$modelOption}";
        }
    }

    /**
     * Userモデルの完全修飾クラス名を取得
     *
     * @param  array  $options
     * @return string
     */
    protected function qualifyUserModel(array $options = []): string
    {
        // --guardオプションが指定されている場合、そのguardのproviderを使用
        if (!empty($options['guard'])) {
            $guard = $options['guard'];
            $provider = config("auth.guards.{$guard}.provider");
            if ($provider) {
                $model = config("auth.providers.{$provider}.model");
                if ($model) {
                    return $model;
                }
            }
        }
        
        // デフォルトのUserモデルを使用
        return config('auth.providers.users.model', 'App\\Models\\User');
    }
}
