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
 * 通知を作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeNotificationTrait
{
    use MakeFileTrait;

    /**
     * 通知クラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) 通知用 stubファイル (例: "notification.stub")
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 通知固有のプレースホルダ (なければ空配列でOK)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * 通知の stub ファイルを指定。将来、--markdown= などで切り替える場合に拡張可能
     */
    protected function resolveStubFile(): string
    {
        return 'notification.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace を通知用にまとめる
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getNotificationDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getNotificationNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getNotificationDirectory(array $subDirs): string;
    abstract protected function getNotificationNamespace(array $subDirs): string;
}
