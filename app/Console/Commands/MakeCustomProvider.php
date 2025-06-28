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
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeProviderTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeCustomCommandTrait;

class MakeCustomProvider extends Command
{
    use MakeProviderTrait;
    use MakeLicenseTrait;
    use MakeCustomCommandTrait;

    protected $signature;

    protected $description = 'Create a new service provider in the custom directory';

    public function __construct()
    {
        $this->signature = $this->makeSignature('make:custom:provider', $this->getAdditionalOptions());
        parent::__construct();
    }

    public function handle()
    {
        // クラス名を取得
        $className = $this->argument('name');
        
        // 共通の初期化処理を実行
        $common = $this->initializeCustomCommand($className);
        if (!$common) {
            return Command::FAILURE;
        }
        
        // プラグイン名を取得
        $pluginName = $this->option('plugin');
        
        // プラグイン名が指定されている場合は、プラグインディレクトリに作成
        if ($pluginName) {
            $common['pluginName'] = $pluginName;
        }
        
        // オプションをマージ
        $options = array_merge($this->options(), [
            'plugin' => $pluginName ?? false
        ]);

        // 関連ファイルの作成
        $handleOptions = $this->handleOptions(
            $className,
            $options,
            $common['fileType'],
            $common['subDirs'] ?? [],
            $common['pluginName'] ?? ''
        );

        // プロバイダーのファイル生成
        $result = $this->makeCustomFile($common, $options);
        
        if ($result) {
            $this->info(__('command.custom.provider.created'));
            return Command::SUCCESS;
        }
        
        $this->error(__('command.custom.provider.failed'));
        return Command::FAILURE;
    }
}
