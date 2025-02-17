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
 * ミドルウェアを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeMiddlewareTrait
{
    use MakeFileTrait;

    /**
     * ミドルウェアを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) ミドルウェア用 stub ファイル (今は常に "middleware.stub" でOK)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) ミドルウェア固有の追加プレースホルダ (特になければ空配列でOK)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders, 'middleware');
    }

    /**
     * stubファイル名を決定
     * 例: "middleware.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'middleware.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace でサブクラスの getMiddlewareDirectory/Namespace を呼ぶ
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getMiddlewareDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getMiddlewareNamespace($subDirs);
    }

    /**
     * サブクラスで実装: getMiddlewareDirectory/Namespace
     */
    abstract protected function getMiddlewareDirectory(array $subDirs): string;
    abstract protected function getMiddlewareNamespace(array $subDirs): string;
}
