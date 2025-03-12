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
 * コンフィグファイル作成の共通ロジック
 */
trait MakeConfigTrait
{
    use MakeFileTrait;

    /**
     * コンフィグファイルを作成する
     *
     * @param string $className
     * @param array $subDirs
     * @param array $options
     */
    protected function makeFile(string $className, array $subDirs, array $options): void
    {
        // コンフィグファイルは "config.stub" を使用
        $stubFile = 'config.stub';

        // ファイル名をスネークケースに変換
        $snakeCaseFileName = Str::snake($className);

        // コンフィグファイルの命名規則を `fileType=config` に指定
        $this->makeFiler($snakeCaseFileName, $subDirs, $options, $stubFile, [], 'config');
    }
}
