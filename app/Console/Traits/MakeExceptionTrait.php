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
 * Exceptionファイル作成用トレイト
 */
trait MakeExceptionTrait
{
    use MakeFileTrait;
    
    /**
     * Exception固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--render : Create the exception with an empty render method}',
            '{--report : Create the exception with an empty report method}',
        ];
    }
    
    /**
     * Exceptionクラスを作成するメイン処理。
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
        
        // ファイル生成
        return $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'exception',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
    }

    /**
     * Exception用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // --renderと--reportオプションによってスタブを切り替え
        $hasRender = !empty($options['render']);
        $hasReport = !empty($options['report']);
        
        if ($hasRender && $hasReport) {
            $stubName = 'exception-render-report.stub';
        } elseif ($hasRender) {
            $stubName = 'exception-render.stub';
        } elseif ($hasReport) {
            $stubName = 'exception-report.stub';
        } else {
            $stubName = 'exception.stub';
        }
        
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
}
