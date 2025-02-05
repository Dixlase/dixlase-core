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
 * テストファイルを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeTestTrait
{
    use MakeFileTrait;

    /**
     * テストファイルを作成するメイン処理。
     *
     * @param  string  $className   テストクラス名
     * @param  array   $subDirs     サブディレクトリ (["Admin", ...] など)
     * @param  bool    $force       --force
     * @param  bool    $isUnit      --unit (true => Unit test, false => Feature test)
     * @param  bool    $usingPest   Pestを使うかどうか
     * @return void
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $isUnit,
        bool $usingPest
    ): void {
        // 1) stubファイル名を決定
        //    "test.stub" / "test.unit.stub" (PHPUnit)
        //    "pest.stub" / "pest.unit.stub" (Pest)
        $stubFile = $this->determineStubFile($isUnit, $usingPest);

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) テスト固有プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * Pest / PHPUnit と unit / feature で stub を切り替える
     */
    protected function determineStubFile(bool $isUnit, bool $usingPest): string
    {
        // suffix
        $suffix = $isUnit ? '.unit.stub' : '.stub';

        if ($usingPest) {
            // pest.stub / pest.unit.stub
            return 'pest' . $suffix;
        } else {
            // test.stub / test.unit.stub
            return 'test' . $suffix;
        }
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getTestDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getTestDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getTestNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getTestDirectory(array $subDirs): string;
    abstract protected function getTestNamespace(array $subDirs): string;
}
