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
 * カスタムバリデーター（または独自バリデーションルール）を作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeValidatorTrait
{
    use MakeFileTrait;

    /**
     * バリデーターを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) validator.stub (rule.stub と呼ぶこともある)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) バリデーター固有の追加プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * validator.stub を返す
     * もしくは `rule.stub` と呼んでもOKです
     */
    protected function resolveStubFile(): string
    {
        return 'validator.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getValidatorDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getValidatorDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getValidatorNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getValidatorDirectory(array $subDirs): string;
    abstract protected function getValidatorNamespace(array $subDirs): string;
}
