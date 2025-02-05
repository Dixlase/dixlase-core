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

trait MakeFactoryTrait
{
    use MakeFileTrait;

    /**
     * ファクトリを作成するメイン処理。
     * MakeFileTrait::makeFiler() を呼ぶ前後で、
     * ファクトリ固有の stub選択 / 追加置換を加える。
     *
     * @param  string       $className
     * @param  bool|string  $modelOption   モデル指定（null/falseの場合なし）
     * @param  bool         $force
     * @return void
     */
    protected function makeFile(string $className, ?string $modelOption, bool $force): void
    {
        // 1) ファクトリ用 stubファイルを決定
        $stubFile = $this->resolveStubFile();

        // 2) ファクトリ固有の追加プレースホルダを組み立て
        //    例: モデルFQCN, クラス名から "FooFactory" → "Foo"
        $modelFqcn  = $this->determineModelFqcn($modelOption, $className);
        $modelClass = class_basename($modelFqcn);
        $justName   = Str::replaceLast('Factory', '', $className);

        $extraPlaceholders = [
            '{{ factory }}'          => $justName,
            '{{ namespacedModel }}'  => $modelFqcn,
            'DummyModel'             => $modelClass,
            '{{ model }}'            => $modelClass,
            '{{ factoryNamespace }}' => $this->getFactoryNamespace(),
        ];

        // 3) $options をまとめる
        //    → MakeFileTrait::makeFiler() で使う連想配列
        $options = [
            'force' => $force,
        ];

        // 4) "makeFiler" を呼び出し
        //    - 第2引数で subDirs を空配列 or 実装に応じて
        //    - 追加プレースホルダを第5引数 (array $extraPlaceholders) などに渡す
        $this->makeFiler(
            $className,
            [],         // subDirs
            $options,
            $stubFile,
            $extraPlaceholders
        );
    }

    /**
     * ファクトリ用 stubファイルを決定
     * （ここでは単純に "factory.stub" で固定 or 拡張対応）
     */
    protected function resolveStubFile(): string
    {
        // 現状は固定 "factory.stub"
        // もし --type=xxx に対応したいなら追加ロジック
        return 'factory.stub';
    }

    /**
     * モデルのFQCNを決定
     *
     * @param string|null $modelOption
     * @param string      $factoryClassName
     * @return string
     */
    protected function determineModelFqcn(?string $modelOption, string $factoryClassName): string
    {
        if ($modelOption) {
            if (Str::startsWith($modelOption, '\\')) {
                $modelOption = Str::replaceFirst('\\', '', $modelOption);
            }
            if (Str::contains($modelOption, '\\')) {
                return $modelOption; // FQCN
            }
            // デフォルト "App\Models\..."
            return 'App\\Models\\' . $modelOption;
        }

        // 工場名から推測: e.g. "UserFactory" => "User"
        $modelName = Str::replaceLast('Factory', '', $factoryClassName);
        $guess     = 'App\\Models\\' . $modelName;

        // クラスがあればそれを使う、なければ fallback
        if (class_exists($guess)) {
            return $guess;
        }
        return 'App\\Models\\Model';
    }

    /**
     * MakeFileTrait が要求する抽象メソッド: getDirectory(array $subDirs), getNamespace(array $subDirs)
     * ここでラップし、それぞれ getFactoryDirectory(), getFactoryNamespace() を呼ぶ
     */
    protected function getDirectory(array $subDirs): string
    {
        // subDirs を使いたい場合は実装を工夫
        return $this->getFactoryDirectory();
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getFactoryNamespace();
    }

    /**
     * サブクラスで実装する抽象メソッド (独自命名を維持)
     */
    abstract protected function getFactoryDirectory(): string;
    abstract protected function getFactoryNamespace(): string;
}
