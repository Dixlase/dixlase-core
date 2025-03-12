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
 * Mailableクラスを作成するための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeMailTrait
{
    use MakeFileTrait;

    /**
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $isMarkdown      --markdown がtrueなら
     * @param  string  $subject         --subject= で指定された文字列
     * @param  string  $view            --view= などで指定されたBlade (markdown のみ？)
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $isMarkdown,
        string $subject = 'Mail Subject',
        string $view = 'view.name'
    ): void {
        // 1) stubファイルを決定
        //    --markdown なら "markdown-mail.stub", なければ "mail.stub"
        $stubFile = $isMarkdown ? 'markdown-mail.stub' : 'mail.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ: subject, view
        $extraPlaceholders = [
            '{{ subject }}' => $subject,
        ];

        // markdown 用 stub の場合: '{{ view }}' => $view
        // 通常 mail.stub は '{{ view }}' が無いがあっても無害
        $extraPlaceholders['{{ view }}'] = $view;

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getMailDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getMailDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getMailNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getMailDirectory(array $subDirs): string;
    abstract protected function getMailNamespace(array $subDirs): string;
}
