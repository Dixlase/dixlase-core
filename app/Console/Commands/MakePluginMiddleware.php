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
use App\Console\Traits\MakeMiddlewareTrait;

class MakePluginMiddleware extends Command
{
    use MakeMiddlewareTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:middleware
        {plugin : The plugin name}
        {name : The name of the middleware (with optional subfolders, e.g. Admin/CheckSomething)}
        {--force : Overwrite if middleware already exists}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new middleware class for the specified plugin.';

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

        // 2) parse className + subDirs
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool) $this->option('force');

        // 4) call trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターンで getMiddlewareDirectory/Namespace を実装
     */
    protected function getMiddlewareDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Http/Middleware");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getMiddlewareNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Http\\Middleware";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
