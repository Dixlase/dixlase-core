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
 * APIリソースを作るためのTrait。
 * -> MakeFileTrait を use し、APIリソース（JsonResource等）生成を共通化
 */
trait MakeApiResourceTrait
{
    use MakeFileTrait;

    /**
     * APIリソースクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) リソース用 stubファイル
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダがあればここに
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "api-resource.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'api-resource.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getApiResourceDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getApiResourceDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getApiResourceNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getApiResourceDirectory(array $subDirs): string;
    abstract protected function getApiResourceNamespace(array $subDirs): string;
}
