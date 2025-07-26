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

trait MakeEnumTrait
{
    use MakeFileTrait;

    /**
     * Enumクラスを作成するメイン処理
     *
     * @param  string       $className
     * @param  array        $subDirs
     * @param  bool         $force
     * @param  string|null  $backedType  --backed=xxx の値 (例: "string" / "int" etc.)
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        ?string $backedType = null
    ): void {
        // 1) stubファイルを決定
        //    --backed=があれば "enum.backed.stub", なければ "enum.stub"
        $isBacked = ! is_null($backedType);
        $stubFile = $isBacked ? 'enum.backed.stub' : 'enum.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) バッキングありなら追加プレースホルダ {{ type }} => $backedType
        $extraPlaceholders = [];
        if ($isBacked) {
            $extraPlaceholders['{{ type }}'] = $backedType;
        }

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getEnumDirectory/Namespace
     *   => サブクラスで実装
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getEnumDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getEnumNamespace($subDirs);
    }

    /**
     * サブクラスに実装してもらう
     */
    abstract protected function getEnumDirectory(array $subDirs): string;
    abstract protected function getEnumNamespace(array $subDirs): string;
}
