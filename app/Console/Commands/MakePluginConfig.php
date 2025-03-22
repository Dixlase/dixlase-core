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
use App\Console\Traits\MakeConfigTrait;

class MakePluginConfig extends Command
{
    use MakeConfigTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:config
        {plugin : The plugin name}
        {name : The name of the config file}
        {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new configuration file for a plugin';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName    = Str::studly($this->argument('plugin'));
        $className = Str::snake($this->argument('name'));

        // プラグインのライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);
        if (!$licenseInfo) {
            return Command::FAILURE;
        }

        $options = [
            'force' => $this->option('force'),
        ];

        $this->makeFile($className, [], $options, $licenseInfo);

        $this->info("プラグイン [{$pluginName}] のコンフィグファイル [{$configName}] を作成しました。");
        return Command::SUCCESS;
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/config");
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getNamespace(array $subDirs): string
    {
        return ''; // コンフィグにはネームスペースは不要
    }
}
