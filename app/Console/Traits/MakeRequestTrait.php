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
 * FormRequest を作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeRequestTrait
{
    use MakeFileTrait;

    /**
     * リクエストクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) リクエスト用 stubファイル (request.stub)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) リクエスト固有のプレースホルダ (無いなら空でOK)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * request.stub 固定 (将来的に --api とかで切り替えたいならここで拡張可)
     */
    protected function resolveStubFile(): string
    {
        return 'request.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getRequestDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getRequestDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getRequestNamespace($subDirs);
    }

    /**
     * サブクラスが実装
     */
    abstract protected function getRequestDirectory(array $subDirs): string;
    abstract protected function getRequestNamespace(array $subDirs): string;
}
