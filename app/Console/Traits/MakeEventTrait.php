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
 * イベントを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeEventTrait
{
    use MakeFileTrait;

    /**
     * イベントを作成するメイン処理 (コントローラやファクトリの makeFile に合わせた命名)
     *
     * @param  string  $className  イベントクラス名
     * @param  array   $subDirs    サブディレクトリ配列 (["Admin", "Sub"]など)
     * @param  bool    $force      上書きフラグ
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) イベント用スタブファイルを決定
        //    今回は単純に "event.stub" を返すだけ
        $stubFile = $this->resolveStubFile();

        // 2) options をまとめる (MakeFileTrait::makeFiler の引数に渡す)
        $options = [
            'force' => $force,
        ];

        // 3) イベント固有の追加プレースホルダ (今回は特になしでOK)
        //    必要であれば '{{ somePlaceholder }}' => 'value' を追加
        $extraPlaceholders = [
            // もし何かあればここで定義
        ];

        // 4) makeFiler を呼び出す
        //    → (クラス名, subDirs, options, stubFile, extraPlaceholders)
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders, 'events');
    }

    /**
     * イベント用 stubファイルを決定。
     * デフォルト "event.stub"。将来的に --type等で拡張も可能
     */
    protected function resolveStubFile(): string
    {
        return 'event.stub';
    }

    /**
     * MakeFileTrait が要求する抽象メソッド:
     *  getDirectory(array $subDirs): string
     *  getNamespace(array $subDirs): string
     *
     * ここで "getEventDirectory" や "getEventNamespace" を呼び出して
     * サブクラスに実装を任せる形にする。(B) パターン
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getEventDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getEventNamespace($subDirs);
    }

    /**
     * サブクラスで実装: Eventのディレクトリ, 名前空間
     */
    abstract protected function getEventDirectory(array $subDirs): string;
    abstract protected function getEventNamespace(array $subDirs): string;
}
