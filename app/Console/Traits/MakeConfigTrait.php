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
 * コンフィグファイル作成の共通ロジック
 */
trait MakeConfigTrait
{
    use MakeFileTrait;
    use MakeLicenseTrait;

    /**
     * コンフィグファイル作成の共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--placeholders= : JSON encoded placeholders for stub replacement}',
            '{--license-info= : JSON encoded license information}',
        ];
    }

    

    /**
     * コンフィグファイルを作成する
     *
     * @param string $className
     * @param array $subDirs
     * @param array $options
     */
    //protected function makeFile(string $className, array $subDirs, array $options): void
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = null)
    {
        //スタブファイルを取得
        $stub = $this->renderStub();
    
        // ファイル名をスネークケースに変換
        $snakeCaseFileName = Str::snake($className);
        
        // プレースホルダーの取得（JSON形式で渡された場合はデコード）
        $additionalPlaceholders = [];
        if (isset($options['placeholders']) && !empty($options['placeholders'])) {
            $decoded = json_decode($options['placeholders'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $additionalPlaceholders = $decoded;
            }
        }
        
        // ライセンスキーからライセンス情報を取得
        $finalLicenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);
        if (isset($options['license-info']) && !empty($options['license-info'])) {
            $licenseKey = $options['license-info'];
            // プラグイン情報を取得
            $pluginInfo = [];
            if (!empty($pluginName)) {
                $licenseInfoFile = base_path("plugins/{$pluginName}/license-info.json");
                if (File::exists($licenseInfoFile)) {
                    $pluginInfo = json_decode(File::get($licenseInfoFile), true) ?? [];
                }
            }
            $finalLicenseInfo = $this->getLicenseInfoFromKey($licenseKey, $pluginInfo);
        }
        
        // ファイル生成
        return $this->makeFiler(
            $snakeCaseFileName,
            $fileType,
            'config',
            $options,
            $subDirs,
            $stub,
            $pluginName,
            $additionalPlaceholders,
            $finalLicenseInfo
        );
        
    }
    /**
     * コンフィグ用のスタブを選択して取得
     *
     * @param  string   $lang
     * @return string
     */
    protected function renderStub(): string
    {
        // 言語用のスタブを選択
        $stubName = 'config.stub';
        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }

}
