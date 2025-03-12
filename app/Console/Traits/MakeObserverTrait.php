<?php

/**
 * This file is part of MySoftware.
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

    /**
     * オブザーバクラスを作成するメイン処理。
     *
     * @param  string  $className   オブザーバクラス名 (e.g. "UserObserver")
     * @param  array   $subDirs     サブディレクトリ
     * @param  bool    $force
     * @param  string|null $modelOption  --modelオプション指定があればモデルFQCNを使用する
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        ?string $modelOption
    ): void {
        // 1) observer.stub
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) オブザーバに関連づくモデルのプレースホルダを決める
        $modelReplacements = $this->buildModelReplacements($modelOption);

        // 4) makeFiler
        //    => ここでは embedLicensePhp などを呼び出す際に
        //       $extraPlaceholders を結合して適用する
        $this->makeFiler($className, $subDirs, $options, $stubFile, $modelReplacements);
    }

    /**
     * デフォルトは observer.stub
     */
    protected function resolveStubFile(): string
    {
        return 'observer.stub';
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
            '{{ namespacedModel }}' => $modelFqcn,
            '{{ model }}'           => $modelShortName,
            '{{ modelVariable }}'   => $modelVariable,
        ];
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getObserverDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getObserverDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getObserverNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getObserverDirectory(array $subDirs): string;
    abstract protected function getObserverNamespace(array $subDirs): string;
}
