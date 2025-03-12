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
 * Inbound Cast (CastsInboundAttributes) を作成するためのTrait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeInboundCastTrait
{
    use MakeFileTrait;

    /**
     * Inbound Castクラスを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) cast.inbound.stub
        $stubFile = 'cast.inbound.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ（なければ空）
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getInboundCastDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getInboundCastDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getInboundCastNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getInboundCastDirectory(array $subDirs): string;
    abstract protected function getInboundCastNamespace(array $subDirs): string;
}
