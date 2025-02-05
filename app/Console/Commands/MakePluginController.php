<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeControllerTrait;

class MakePluginController extends Command
{
    use MakeControllerTrait;

    /**
     * Artisan コマンド名と引数/オプション定義
     * 例: php artisan plugin:make:controller my-plugin MyController
     */
    protected $signature = 'make:plugin:controller
        {plugin : The plugin name}
        {name : The controller name}
        {--force}
        {--invokable}
        {--model=}
        {--parent=}
        {--resource}
        {--requests}
        {--api}
        {--singleton}
        {--creatable}
        {--type=}';

    protected $description = 'Create a new controller for the specified plugin';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $plugin    = Str::studly($this->argument('plugin'));
        $path      = str_replace('\\', '/', $this->argument('name'));
        $parts     = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $parts;

        $options = [
            'force'     => $this->option('force'),
            'invokable' => $this->option('invokable'),
            'model'     => $this->option('model'),
            'parent'    => $this->option('parent'),
            'resource'  => $this->option('resource'),
            'requests'  => $this->option('requests'),
            'api'       => $this->option('api'),
            'singleton' => $this->option('singleton'),
            'creatable' => $this->option('creatable'),
            'type'      => $this->option('type'),
        ];

        // use "makeFile" for the final call
        $this->makeFile($className, $subDirs, $options);

        return 0;
    }

    /**
     * @override
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base   = base_path("plugins/{$pluginName}/app/Http/Controllers");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    /**
     * @override
     */
    protected function getNamespace(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base   = "Plugins\\{$pluginName}\\App\\Http\\Controllers";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
