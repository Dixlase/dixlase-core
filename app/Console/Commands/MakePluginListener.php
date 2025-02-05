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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:make:listener
                            {plugin : The plugin name}
                            {name : The name of the listener}
                            {--force : Create the class even if the listener already exists}
                            {--event= : The event class being listened for}
                            {--queued : Indicates the event listener should be queued}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event listener class for the specified plugin.';

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
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force, --event=..., --queued
        $force     = (bool) $this->option('force');
        $queued    = (bool) $this->option('queued');
        $eventOpt  = $this->option('event');

        // 4) イベントクラスをFQCNに変換 (プラグイン用イベントとみなす or FQCN)
        $eventClass = $eventOpt ? $this->qualifyEventClass($eventOpt, $pluginName) : null;

        // 5) Traitの makeFile(...) 呼び出し
        $this->makeFile($className, $subDirs, $force, $queued, $eventClass);

        return 0;
    }

    /**
     * イベントクラスをFQCNに変換
     * 例: "OrderCreated" => "Plugins\MyPlugin\App\Events\OrderCreated"
     *    or 既に FQCN => そのまま
     */
    protected function qualifyEventClass(string $eventOption, string $pluginName): string
    {
        if (Str::startsWith($eventOption, '\\')) {
            $eventOption = Str::replaceFirst('\\', '', $eventOption);
        }
        if (Str::contains($eventOption, '\\')) {
            return $eventOption;
        }
        return "Plugins\\{$pluginName}\\App\\Events\\{$eventOption}";
    }

    /**
     * getListenerDirectory/Namespace
     */
    protected function getListenerDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$pluginName}/app/Listeners");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getListenerNamespace(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$pluginName}\\App\\Listeners";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
