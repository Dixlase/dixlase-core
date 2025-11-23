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
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;

/**
 * Artisanコマンドクラスを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeCommandTrait
{
    use MakeFileTrait;
    use MakeLicenseTrait;

    /**
     * コマンドコマンドの共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [];
    }

    /**
     * Artisanコマンドクラスを作成するメイン処理。
     *
     * @param  string  $className   コマンドクラス名
     * @param  string  $fileType    ファイルタイプ
     * @param  array   $options     オプション配列
     * @param  array   $subDirs     サブディレクトリ配列
     * @param  string  $pluginName  プラグイン名
     * @param  array   $licenseInfo ライセンス情報
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '', array $licenseInfo = [])
    {
        // 1) command.stubの内容を読み込み
        $stubPath = base_path('stubs/custom/command.stub');
        $stub = file_get_contents($stubPath);

        // 2) ライセンス情報の取得（渡された情報を優先）
        if (empty($licenseInfo)) {
            $licenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);
        }

        // 3) ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'command',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $licenseInfo
        );

        return true;
    }
}
