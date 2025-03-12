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

class MakePluginComponent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:component
                            {plugin : The plugin name}
                            {name : The name of the view component}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view component class for the specified plugin.';

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
        $pluginName = Str::studly($this->argument('plugin'));
        $componentName = $this->argument('name');

        $namespace = "Plugins\\{$pluginName}\\App\\View\\Components";
        $filePath  = base_path("plugins/{$pluginName}/app/View/Components/{$componentName}.php");

        try {
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Component [{$componentName}] already exists in plugin [{$pluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $stub = $this->fileGenerator->getStubContent(
            'component.stub',
            null,
            [base_path('stubs/custom')]
        );

        $content = $this->fileGenerator->embedLicensePhp($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $componentName,
        ]);

        $this->fileGenerator->generateFile($filePath, $content);
        $this->info("View Component [{$componentName}] created successfully in plugin [{$pluginName}].");

        return Command::SUCCESS;
    }
}
