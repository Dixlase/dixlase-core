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
use App\Console\Traits\MakeFactoryTrait;

class MakePluginFactory extends Command
{
    use MakeFactoryTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:factory
                            {plugin : The name of the plugin (e.g. "EventsPlugin")}
                            {name : The name of the factory class (e.g. "EventFactory" or just "Event")}
                            {--model= : The name of the model (FQCN or relative) for this factory}
                            {--force : Overwrite the factory if it already exists}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory in the specified plugin directory';

    /**
     * ファイル操作用のインスタンス
     */
    protected FileGenerator $fileGenerator;

    /**
     * コンストラクタ（FilesystemのDIなどに利用）
     */
    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $plugin    = Str::studly($this->argument('plugin'));
        $className = $this->argument('name');
        if (! Str::endsWith($className, 'Factory')) {
            $className .= 'Factory';
        }

        $model = $this->option('model');
        $force = (bool) $this->option('force');

        // ファクトリ作成
        $this->makeFile($className, $model, $force);

        return 0;
    }

    /**
     * サブクラスで実装: getFactoryDirectory(), getFactoryNamespace()
     */
    protected function getFactoryDirectory(): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/database/factories");
    }

    protected function getFactoryNamespace(): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return "Plugins\\{$pluginName}\\Database\\Factories";
    }
}
