<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
 * コントローラを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeControllerTrait
{
    use MakeFileTrait;

    /**
     * コントローラを作成するメイン処理。
     * MakeFileTrait::makeFiler() を呼ぶ前後で、
     * コントローラ固有の stub選択 / 追加置換を加える。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  array   $options
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, array $options): void
    {
        // 1) コントローラ特有の stubファイルを決定
        $stubFile = $this->resolveStubFile($options);

        // 2) コントローラ固有の追加置換ロジックが必要なら、ここでフックを作ってもいいが
        //    今回は "makeFiler" の中で最終的に埋め込み実行する形でもOK

        // 3) まず "makeFiler" を呼んで、基本的なファイル出力フローを実行
        //    （getDirectory() / getNamespace() / loadStubFile() / embedLicense() など）
        $this->makeFiler($className, $subDirs, $options, $stubFile);
    }

    /**
     * コントローラ用 stubファイル名を決定
     */
    protected function resolveStubFile(array $options): string
    {
        // 例: --type=xxx → "controller.xxx.stub"
        if (! empty($options['type'])) {
            return "controller.{$options['type']}.stub";
        }
        if (! empty($options['parent'])) {
            return ! empty($options['singleton'])
                ? 'controller.nested.singleton.stub'
                : 'controller.nested.stub';
        }
        if (! empty($options['model'])) {
            return ! empty($options['invokable'])
                ? 'controller.model.api.stub'
                : 'controller.model.stub';
        }
        if (! empty($options['invokable'])) {
            return 'controller.invokable.stub';
        }
        if (! empty($options['singleton'])) {
            return 'controller.singleton.stub';
        }
        $stub = ! empty($options['resource'])
            ? 'controller.stub'
            : 'controller.plain.stub';
        if (! empty($options['api'])) {
            if ($stub === 'controller.plain.stub') {
                $stub = 'controller.api.stub';
            } elseif ($stub !== 'controller.invokable.stub') {
                $stub = str_replace('.stub', '.api.stub', $stub);
            }
        }
        return $stub;
    }

    /**
     * Models, Requests, etc. コントローラ特有の置換処理を
     * makeFiler() 内部にフックインする例として設計してもOK。
     * ここでは省略し、最小限にとどめる。
     */
}
