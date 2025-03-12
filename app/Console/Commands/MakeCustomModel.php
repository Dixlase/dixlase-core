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
use App\Console\Traits\MakeModelTrait;

class MakeCustomModel extends Command
{
    use MakeModelTrait;

    protected $signature = 'make:custom:model
        {name : Model name (e.g. Admin/MyModel)}
        {--all : Generate migration, factory, seeder, etc.}
        {--factory : ...}
        {--force : ...}
        {--migration : ...}
        {--morph-pivot : ...}
        {--policy : ...}
        {--seed : ...}
        {--pivot : ...}
        {--resource : ...}
        {--api : ...}
        {--requests : ...}
    ';

    protected $description = 'Create a new model in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('requests', true);
        }

        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        $force      = (bool)$this->option('force');
        $pivot      = (bool)$this->option('pivot');
        $morphPivot = (bool)$this->option('morph-pivot');

        // makeFile => trait
        $modelFqcn = $this->makeFile($className, $subDirs, $force, $pivot, $morphPivot);

        // 追加生成
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
     * モデルのディレクトリ/名前空間 (Bパターン)
     */
    protected function getModelDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Models');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getModelNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Models';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }

    // 以下、createFactory, createMigration, createSeeder, createController, createFormRequests, createPolicy は
    // plugin版とほぼ同じ。 "make:plugin::factory" → "make:custom:factory" などに置き換える。

    protected function createFactory(string $modelFqcn)
    {
        $modelBase = class_basename($modelFqcn);
        $factoryName = "{$modelBase}Factory";

        $this->call('make:custom:factory', [
            'name'   => $factoryName,
            '--model' => $modelFqcn,
            '--force' => false,
        ]);
    }

    protected function createMigration(string $className, bool $isPivot = false)
    {
        $table = Str::snake(Str::pluralStudly($className));
        if ($isPivot) {
            $table = Str::singular($table);
        }
        $migrationName = "create_{$table}_table";

        $this->call('make:custom:migration', [
            'name'   => $migrationName,
            '--create' => $table,
        ]);
    }

    protected function createSeeder(string $className)
    {
        $seeder = Str::studly($className) . 'Seeder';
        $this->call('make:custom:seeder', [
            'name'  => $seeder,
        ]);
    }

    protected function createController(string $modelFqcn)
    {
        $ctrlName = class_basename($modelFqcn) . 'Controller';
        $this->call('make:custom:controller', array_filter([
            'name'   => $ctrlName,
            '--model' => ($this->option('api') || $this->option('resource')) ? $modelFqcn : null,
            '--api'  => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--resource' => $this->option('resource'),
        ]));
    }

    protected function createFormRequests(string $className)
    {
        $basename = Str::studly($className);
        $this->call('make:custom:request', [
            'name'   => 'Store' . $basename . 'Request',
        ]);
        $this->call('make:custom:request', [
            'name'   => 'Update' . $basename . 'Request',
        ]);
    }

    protected function createPolicy(string $modelFqcn)
    {
        $policy = class_basename($modelFqcn) . 'Policy';
        $this->call('make:custom:policy', [
            'name'  => $policy,
            '--model' => $modelFqcn,
        ]);
    }
}
