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
 * Bladeファイルを作成するためのTrait。
 * -> いわゆる「クラス + namespace」が存在しないため、MakeFileTraitは使わず、
 *    シンプルに「フォルダ+ファイル」を生成するだけに特化する。
 */
trait MakeBladeTrait
{
    use MakeFileTrait;

    /**
     * Blade ファイルを作成する
     *
     * @param string $viewName 例: "admin/dashboard"
     * @param array $options
     */

    protected function makeFile(string $viewName, array $subDirs, array $options): void
    {
        // Bladeファイルの拡張子を追加
        if (str_ends_with(
            $viewName,
            '.php'
        )) {
            $viewName = substr($viewName, 0, -4); // すでに .php 付きなら削除
        }

        if (! str_ends_with($viewName, '.blade')) {
            $viewName .= '.blade'; // `makeFiler()` で `.php` を追加するので `.blade` だけつける
        }

        // ファイル名をケバブケースに変換
        $kebabCaseFileName = Str::kebab(str_replace('/', '-', $viewName));

        // Blade用のライセンスコメント
        $extraPlaceholders = [
            '{{ license }}' => $this->fileGenerator->getLicenseForBlade(),
            '{{ filename }}' => str_replace('.blade.php', '', $viewName),
        ];

        // `makeFiler()` を使用してファイルを生成
        $this->makeFiler($kebabCaseFileName, $subDirs, $options, $this->resolveStubFile($options), $extraPlaceholders, 'views');
    }

    /**
     * フロント用・管理画面用のBladeテンプレートを選択
     */
    protected function resolveStubFile(array $options): string
    {
        return ($options['type'] ?? 'front') === 'admin' ? 'blade-admin.stub' : 'blade-front.stub';
    }
}
