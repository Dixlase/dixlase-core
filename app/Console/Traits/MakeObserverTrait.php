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
 * Eloquent オブザーバ (Observer) 作成のための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 *
 * observer.stub には以下のプレースホルダがあると想定:
 *  - {{ license }}
 *  - {{ namespace }}
 *  - {{ class }}
 *  - {{ namespacedModel }}
 *  - {{ model }}
 *  - {{ modelVariable }}
 */
trait MakeObserverTrait
{
    use MakeFileTrait;
    use MakeLicenseTrait;

    /**
     * オブザーバーコマンドの共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--m|model= : ' . __('commands.make.options.model') . '}',
        ];
    }

    /**
     * オブザーバクラスを作成するメイン処理。
     *
     * @param  string  $className   オブザーバクラス名 (e.g. "UserObserver")
     * @param  string  $fileType    ファイルタイプ
     * @param  array   $options     オプション配列
     * @param  array   $subDirs     サブディレクトリ配列
     * @param  string  $pluginName  プラグイン名
     * @param  array   $licenseInfo ライセンス情報
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '', array $licenseInfo = [])
    {
        // 1) observer.stubの内容を読み込み
        $stubPath = base_path('stubs/custom/observer.stub');
        $stub = file_get_contents($stubPath);

        // 2) オブザーバに関連づくモデルのプレースホルダを決める
        $modelOption = $options['model'] ?? null;
        $modelReplacements = $this->buildModelReplacements($modelOption);

        // 3) ライセンス情報の取得（渡された情報を優先）
        if (empty($licenseInfo)) {
            $licenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);
        }

        // 4) ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'observer',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $modelReplacements,
            licenseInfo: $licenseInfo
        );

        return true;
    }


    /**
     * --model=xxx を指定した場合、そのFQCNを取得し、stubの {{ namespacedModel }} / {{ model }} / {{ modelVariable }} を置換
     */
    protected function buildModelReplacements(?string $modelOption): array
    {
        if ($modelOption) {
            // 先頭が '\' なら削除
            if (Str::startsWith($modelOption, '\\')) {
                $modelOption = Str::replaceFirst('\\', '', $modelOption);
            }

            // 既に FQCN (App\～) ならそのまま、そうでなければ "App\Models\～" とかに補完など好みに応じて
            $modelFqcn = Str::contains($modelOption, '\\')
                ? $modelOption
                : 'App\\Models\\' . $modelOption;
        } else {
            // fallback: "App\Models\Sample"
            $modelFqcn = 'App\\Models\\Sample';
        }

        $modelShortName  = class_basename($modelFqcn);
        $modelVariable   = Str::camel($modelShortName);

        return [
            'namespacedModel' => $modelFqcn,
            'model'           => $modelShortName,
            'modelVariable'   => $modelVariable,
        ];
    }

}
