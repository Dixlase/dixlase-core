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
 * Artisanコマンドクラスを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeCommandTrait
{
    use MakeFileTrait;

    /**
     * Artisanコマンドクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) command.stub など
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) Artisanコマンド特有のプレースホルダ(なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "command.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'command.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getCommandDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getCommandDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getCommandNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getCommandDirectory(array $subDirs): string;
    abstract protected function getCommandNamespace(array $subDirs): string;
}
