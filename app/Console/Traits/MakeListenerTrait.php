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
 * リスナーを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeListenerTrait
{
    use MakeFileTrait;

    /**
     * リスナーを作成するメイン処理。
     *
     * @param  string       $className  リスナークラス名
     * @param  array        $subDirs    サブディレクトリ (["Admin", "Nested"] 等)
     * @param  bool         $force
     * @param  bool         $queued     --queued
     * @param  string|null  $eventClass --event= のFQCN (nullの場合は「object $event」扱い)
     * @return void
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $queued,
        ?string $eventClass
    ): void {
        // 1) stubファイル名を決定
        $stubFile = $this->determineStubFile($eventClass, $queued);

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) リスナー固有の追加プレースホルダ
        //    例) typed vs object, eventNamespace
        $extraPlaceholders = [
            '{{ event }}'          => $eventClass ? class_basename($eventClass) : 'object',
            '{{ eventNamespace }}' => $eventClass ?? '',
        ];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders, 'listeners');
    }

    /**
     * stubファイルの決定
     * 例:
     *   listener.stub
     *   listener.queued.stub
     *   listener.typed.stub
     *   listener.typed.queued.stub
     */
    protected function determineStubFile(?string $eventClass, bool $queued): string
    {
        if ($eventClass && $queued) {
            return 'listener.typed.queued.stub';
        } elseif ($eventClass) {
            return 'listener.typed.stub';
        } elseif ($queued) {
            return 'listener.queued.stub';
        } else {
            return 'listener.stub';
        }
    }

    /**
     * (B)パターン: getDirectory() / getNamespace() を本Trait内でオーバーライドし、
     *   getListenerDirectory() / getListenerNamespace() をサブクラスで定義させる
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getListenerDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getListenerNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getListenerDirectory(array $subDirs): string;
    abstract protected function getListenerNamespace(array $subDirs): string;
}
