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
use App\Console\Traits\MakeModelTrait;

class MakePluginModel extends Command
{
    protected $signature = 'make:plugin:model
        {plugin : The plugin name}
        {name : The name of the model (optionally with subfolders, e.g. Admin/MyModel)}
        {--all : Generate migration, seeder, factory, policy, resource controller, and form request classes for the model}
        {--controller : Create a new controller for the model}
        {--factory : Create a new factory for the model}
        {--force : Create the class even if the model already exists}
        {--migration : Create a new migration file for the model}
        {--morph-pivot : Indicates if the generated model should be a custom polymorphic pivot model}
        {--policy : Create a new policy for the model}
        {--seed : Create a new seeder for the model}
        {--pivot : Indicates if the generated model should be a custom intermediate table model (pivot)}
        {--resource : Indicates if the generated controller should be a resource controller}
        {--api : Indicates if the generated controller should be an API resource controller}
        {--requests : Create new form request classes for the controller}
    ';

    protected $description = 'Create a new Eloquent model class for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin, model, pivot, morphPivot
        $pluginNameInput = $this->argument('plugin');
        $modelInput      = $this->argument('name');
        $force           = (bool)$this->option('force');
        $pivot           = (bool)$this->option('pivot');
        $morphPivot      = (bool)$this->option('morph-pivot');

        // 2) if --all => set other options
        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('requests', true);
        }

        // 3) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($modelInput);

        // 4) call trait method
        $modelFqcn = $this->makeFile($className, $subDirs, $force, $pivot, $morphPivot);

        // 5) after creation => additional generation
        if ($this->option('factory')) {
            $this->createFactory($modelFqcn);
        }
        if ($this->option('migration')) {
            $this->createMigration($className, $pivot || $morphPivot);
        }
        if ($this->option('seed')) {
            $this->createSeeder($className);
        }
        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController($modelFqcn);
        } elseif ($this->option('requests')) {
            $this->createFormRequests($className);
        }
        if ($this->option('policy')) {
            $this->createPolicy($modelFqcn);
        }

        return 0;
    }

    /**
     * サブクラス実装: getModelDirectory($subDirs), getModelNamespace($subDirs)
     */
    protected function getModelDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Models");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getModelNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Models";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }

    /**
     * 追加生成: factory
     */
    protected function createFactory(string $modelFqcn)
    {
        $modelBase = class_basename($modelFqcn);
        $factoryName = "{$modelBase}Factory";

        $this->call('make:plugin:factory', [
            'plugin' => $this->argument('plugin'),
            'name'   => $factoryName,
            '--model' => $modelFqcn,
            '--force' => false,
        ]);
    }

    /**
     * 追加生成: migration
     */
    protected function createMigration(string $className, bool $pivot = false)
    {
        $table = Str::snake(Str::pluralStudly($className));
        if ($pivot) {
            $table = Str::singular($table);
        }
        $migrationName = "create_{$table}_table";

        $this->call('make:plugin:migration', [
            'plugin' => $this->argument('plugin'),
            'name'   => $migrationName,
            '--create' => $table,
        ]);
    }

    /**
     * 追加生成: seeder
     */
    protected function createSeeder(string $className)
    {
        $seeder = Str::studly($className) . 'Seeder';
        $this->call('make:plugin:seeder', [
            'plugin' => $this->argument('plugin'),
            'name'  => $seeder,
        ]);
    }

    /**
     * 追加生成: controller
     */
    protected function createController(string $modelFqcn)
    {
        $ctrlName = class_basename($modelFqcn) . 'Controller';
        $this->call('make:plugin:controller', array_filter([
            'plugin' => $this->argument('plugin'),
            'name'   => $ctrlName,
            '--model' => ($this->option('api') || $this->option('resource')) ? $modelFqcn : null,
            '--api'  => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--resource' => $this->option('resource'),
        ]));
    }

    /**
     * 追加生成: form requests
     */
    protected function createFormRequests(string $className)
    {
        $basename = Str::studly($className);
        $this->call('make:plugin:request', [
            'plugin' => $this->argument('plugin'),
            'name'   => 'Store' . $basename . 'Request',
        ]);
        $this->call('make:plugin:request', [
            'plugin' => $this->argument('plugin'),
            'name'   => 'Update' . $basename . 'Request',
        ]);
    }

    /**
     * 追加生成: policy
     */
    protected function createPolicy(string $modelFqcn)
    {
        $policy = class_basename($modelFqcn) . 'Policy';
        $this->call('make:plugin:policy', [
            'plugin' => $this->argument('plugin'),
            'name'  => $policy,
            '--model' => $modelFqcn,
        ]);
    }
}
