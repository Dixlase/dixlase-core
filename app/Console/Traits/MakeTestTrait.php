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
 * テストファイル作成用トレイト
 */
trait MakeTestTrait
{
    use MakeFileTrait;
    
    /**
     * Test固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--u|unit : Create a unit test}',
            '{--pest : Create a Pest test}',
            '{--phpunit : Create a PHPUnit test}',
        ];
    }
    
    /**
     * テストファイルを作成するメイン処理。
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
        
        // Unit testかどうかに応じてサブディレクトリを調整
        $testType = !empty($options['unit']) ? 'Unit' : 'Feature';
        $adjustedSubDirs = array_merge([$testType], $subDirs);
        
        // プレースホルダーの準備
        $placeholders = [];
        
        // ファイル生成
        return $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'test',
            options: $options,
            subDirs: $adjustedSubDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
    }

    /**
     * Test用のスタブをレンダリングします。
     *
     * @param  array  $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // Pestを使うかどうかを判定
        $usingPest = $this->usingPest($options);
        
        // Unit testかどうか
        $isUnit = !empty($options['unit']);
        
        // スタブ名を決定
        $suffix = $isUnit ? '.unit.stub' : '.stub';
        $stubName = $usingPest ? ('pest' . $suffix) : ('test' . $suffix);
        
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
    
    /**
     * Pestを使うかどうかを判定
     *
     * @param  array  $options
     * @return bool
     */
    protected function usingPest(array $options): bool
    {
        if (!empty($options['phpunit'])) {
            return false;
        }

        if (!empty($options['pest'])) {
            return true;
        }

        // Pestがインストール済みかどうかを簡易チェック
        return function_exists('\\Pest\\version') && file_exists(base_path('tests/Pest.php'));
    }
}
