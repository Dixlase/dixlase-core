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

/**
 * サービスプロバイダ作成用の追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeProviderTrait
{
    use MakeFileTrait;

    /**
     * プロバイダを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $pluginStub  // plugin固有のスタブを使うかどうか (例: provider.plugin.stub)
     */
    protected function makeFile(string $className, array $subDirs, bool $force, bool $pluginStub = false): void
    {
        // 1) stubファイルを決定
        //    provider.stub or provider.plugin.stub
        $stubFile = $pluginStub ? 'provider.plugin.stub' : 'provider.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) プロバイダ固有の追加プレースホルダ (なければ空)
        $extraPlaceholders = [
            // e.g. '{{ pluginName }}' => ...
        ];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory(), getNamespace() => getProviderDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getProviderDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getProviderNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getProviderDirectory(array $subDirs): string;
    abstract protected function getProviderNamespace(array $subDirs): string;
}
