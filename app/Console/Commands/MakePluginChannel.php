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
use App\Console\Traits\MakeChannelTrait;

class MakePluginChannel extends Command
{
    use MakeChannelTrait;

    protected $signature = 'make:plugin:channel
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The channel class name (optionally with subfolders, e.g. Admin/MyChannel)}
        {--force : Overwrite if the channel class already exists}';

    protected $description = 'Create a new broadcasting channel class in the specified plugin directory';

    protected FileGenerator $fileGenerator;

    protected string $pluginName;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin name
        $this->pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getChannelDirectory/Namespace
     */
    protected function getChannelDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Broadcasting"
        $base = base_path("plugins/{$this->pluginName}/app/Broadcasting");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getChannelNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Broadcasting"
        $base = "Plugins\\{$this->pluginName}\\App\\Broadcasting";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
