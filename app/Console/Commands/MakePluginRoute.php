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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Traits\MakeRouteTrait;
use App\Console\Traits\MakeFileTrait;
use App\Console\Traits\MakePluginCommandTrait;

class MakePluginRoute extends Command
{
    use MakeRouteTrait;
    use MakeFileTrait;
    use MakePluginCommandTrait;

    protected $signature;

    protected $description = 'Create a new route file for a plugin';

    public function __construct()
    {
        $this->signature = $this->makeSignature('make:plugin:route {className} {pluginName?}', $this->getAdditionalOptions());
        parent::__construct();
    }

    public function handle()
    {
        // ルートファイル用の初期化処理
        $common = $this->initializeRouteCommand($this->argument('className'));
        if (!$common) {
            return Command::FAILURE;
        }

        // オプションを取得
        $options = $this->options();

        // 関連ファイルの作成
        $handleOptions = $this->handleOptions(
            $this->argument('className'),
            $options,
            $common['fileType'],
            $common['subDirs'],
            $common['pluginName'] ?? ''
        );

        // 関連ファイルの作成に失敗した場合は、コマンドを終了
        if (!$handleOptions) {
            return Command::FAILURE;
        }

        // ルートファイルの生成
        return $this->makePluginFile($common, $options)
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
