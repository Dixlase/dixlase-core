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
 * シーダーを作成するためのトレイト
 */
trait MakeSeederTrait
{
    use MakeFileTrait;

    /**
     * シーダー固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--license-info= : JSON encoded license information}',
        ];
    }

    /**
     * シーダーファイルを作成する
     *
     * @param string $className
     * @param string $fileType
     * @param array $options
     * @param array $subDirs
     * @param string $pluginName
     * @return bool
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName
    ): bool {
        // Get the stub content
        $stub = $this->renderStub($options);
        
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
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'seeder',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $finalLicenseInfo
        );

        return true;

    }

    /**
     * スタブをレンダリングする
     *
     * @param array $options
     * @return string
     */
    protected function renderStub(array $options): string
    {
        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/seeder.stub';

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
}
