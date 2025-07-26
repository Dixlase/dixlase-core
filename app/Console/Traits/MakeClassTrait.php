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

/**
 * 汎用クラスを作成するためのTrait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeClassTrait
{
    use MakeFileTrait;

    /**
     * 汎用クラス (invokable可) を作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $isInvokable  --invokable がtrueなら
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $isInvokable
    ): void {
        // 1) stubファイルを決定
        //    --invokable なら "class.invokable.stub"、
        //    そうでなければ "class.stub"
        $stubFile = $isInvokable ? 'class.invokable.stub' : 'class.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getClassDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getClassDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getClassNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getClassDirectory(array $subDirs): string;
    abstract protected function getClassNamespace(array $subDirs): string;
}
