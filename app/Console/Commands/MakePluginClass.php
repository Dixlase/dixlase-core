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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeClassTrait;

class MakePluginClass extends Command
{
    use MakeClassTrait;

    protected $signature = 'make:plugin:class
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The class name (e.g. Admin/UtilityClass)}
        {--invokable : Generate an invokable class (__invoke())}
        {--force : Overwrite if the class already exists}';

    protected $description = 'Create a new generic class in the specified plugin directory';

    protected FileGenerator $fileGenerator;
    protected string $pluginName;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin
        $this->pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --invokable
        $isInvokable = (bool)$this->option('invokable');

        // 4) --force
        $force = (bool)$this->option('force');

        // 5) trait method
        $this->makeFile($className, $subDirs, $force, $isInvokable);

        return 0;
    }

    /**
     * (B)パターン: getClassDirectory/Namespace
     */
    protected function getClassDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Classes"
        $base = base_path("plugins/{$this->pluginName}/app/Classes");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getClassNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Classes"
        $base = "Plugins\\{$this->pluginName}\\App\\Classes";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
