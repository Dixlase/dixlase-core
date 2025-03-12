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
 * ジョブを作成するための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeJobTrait
{
    use MakeFileTrait;

    /**
     * ジョブを作成するメイン処理。
     * MakeFileTrait::makeFiler() を呼ぶ前後で、
     * ジョブ固有の stub選択 (sync or queued) / 追加置換を加える。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $sync    --sync
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force, bool $sync): void
    {
        // 1) ジョブ用 stubファイルを決定 (sync/queued)
        $stubFile = $sync ? 'job.stub' : 'job.queued.stub';

        // 2) options をまとめる
        $options = [
            'force' => $force,
        ];

        // 3) ジョブ固有の追加プレースホルダ (必要なければ空配列)
        // ここでは何もない場合、たとえば queued jobに追加するものがあれば足す
        $extraPlaceholders = [
            // e.g. '{{ additional }}' => 'some-value'
        ];

        // 4) makeFiler を呼んで基本のファイル生成フローを実行
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders, 'jobs');
    }

    /**
     * MakeFileTrait が要求する抽象メソッド:
     *   - getDirectory(array $subDirs)
     *   - getNamespace(array $subDirs)
     * ここでは (B)パターンを踏襲し、サブクラスの "getJobDirectory/Namespace" を呼ぶ
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getJobDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getJobNamespace($subDirs);
    }

    /**
     * サブクラスにて実装:
     *   abstract protected function getJobDirectory(array $subDirs): string;
     *   abstract protected function getJobNamespace(array $subDirs): string;
     */
    abstract protected function getJobDirectory(array $subDirs): string;
    abstract protected function getJobNamespace(array $subDirs): string;
}
