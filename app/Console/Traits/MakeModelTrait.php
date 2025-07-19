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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * モデル作成用トレイト。
 * -> MakeFileTrait を use し、モデル特有の (pivot, morphPivot, --all, etc.) ロジックを追加。
 */
trait MakeModelTrait
{
    use MakeFileTrait;
    
    /**
     * モデル固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--all : ' . __("command.make.options.all") . '}',
            '{--controller : ' . __("command.make.options.controller") . '}',
            '{--factory : ' . __("command.make.options.factory") . '}',
            '{--migration : ' . __("command.make.options.migration") . '}',
            '{--morph-pivot : ' . __("command.make.options.morph-pivot") . '}',
            '{--policy : ' . __("command.make.options.policy") . '}',
            '{--seed : ' . __("command.make.options.seed") . '}',
            '{--pivot : ' . __("command.make.options.pivot") . '}',
            '{--resource : ' . __("command.make.options.resource") . '}',
            '{--api : ' . __("command.make.options.api") . '}',
            '{--requests : ' . __("command.make.options.requests") . '}',
        ];
    }

    
    /**
     * モデルを作成するメイン処理。
     *
     * @param  string  $className     モデルクラス名 (e.g. "Post")
     * @param  array   $subDirs       サブディレクトリ (["Admin"] など)
     * @param  bool    $force         --force
     * @param  bool    $pivot         --pivot
     * @param  bool    $morphPivot    --morph-pivot
     * @return void
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '')
    {
        // スタブの取得
       // $stubPath = $this->getStub();
        $stub = $this->renderStub($options);
        
        // ファクトリー関連の置換を追加
        //$factoryReplacements = $this->buildFactoryReplacements($className, $fileType, $subDirs, $pluginName);
        
        // ファイル生成
        return $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'models',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
    }

    /**
     * モデル用のスタブを選択して取得
     *
     * @param  string  $scope
     * @param  array   $options
     * @return string
     */
    protected function renderStub(array $options = []): string
    {
        // モデル用のスタブを選択
        $stubName = 'model.stub';
        if ($options['pivot'] ?? false) {
            $stubName = 'model.pivot.stub';
        } elseif ($options['morphPivot'] ?? false) {
            $stubName = 'model.morph-pivot.stub';
        }

        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }

    
    /**
     * Handle model creation and related files
     *
     * @param string $className
     * @param array $options
     * @param string $fileType
     * @param array $subDirs
     * @param string $pluginName
     * @return bool
     */
    protected function handleOptions($className, $options, $fileType, $subDirs = [], $pluginName = '')
    {
        // Handle related files based on options
        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory($className, $fileType, $subDirs, $pluginName);
        }

        if ($this->option('migration')) {
            $this->createMigration($className, $fileType, $subDirs, $pluginName);
        }

        if ($this->option('seed')) {
            $this->createSeeder($className, $fileType, $subDirs, $pluginName);
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController($className, $fileType, $subDirs, $pluginName);
        } elseif ($this->option('requests')) {
            $this->createFormRequests($className, $fileType, $subDirs, $pluginName);
        }

        if ($this->option('policy')) {
            $this->createPolicy($className, $fileType, $subDirs, $pluginName);
        }

        return true;
    }

    /**
     * Create a factory for the model
     */
    protected function createFactory($className, $fileType, $subDirs, $pluginName = '')
    {
        $factoryName = $className . 'Factory';
        $this->call('make:custom:factory', [
            'name' => $factoryName,
            '--model' => $this->qualifyModel($className, $fileType, $subDirs, $pluginName),
        ]);
    }

    /**
     * Create a migration for the model
     */
    protected function createMigration($className, $fileType, $subDirs, $pluginName = '')
    {
        $table = Str::snake(Str::pluralStudly(class_basename($className)));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('make:custom:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder for the model
     */
    protected function createSeeder($className, $fileType, $subDirs, $pluginName = '')
    {
        $seeder = class_basename($className) . 'Seeder';
        $this->call('make:custom:seeder', [
            'name' => $seeder,
        ]);
    }

    /**
     * Create a controller for the model
     */
    protected function createController($className, $fileType, $subDirs, $pluginName = '')
    {
        $controllerName = class_basename($className) . 'Controller';
        $modelName = $this->qualifyModel($className, $fileType, $subDirs, $pluginName);

        $this->call('make:custom:controller', array_filter([
            'name' => $controllerName,
            '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--resource' => $this->option('resource'),
        ]));
    }

    /**
     * Create form request classes for the model
     */
    protected function createFormRequests($className, $fileType, $subDirs, $pluginName = '')
    {
        $requestName = class_basename($className) . 'Request';
        $this->call('make:custom:request', [
            'name' => 'Store' . $requestName,
        ]);
        $this->call('make:custom:request', [
            'name' => 'Update' . $requestName,
        ]);
    }

    /**
     * Create a policy for the model
     */
    protected function createPolicy($className, $fileType, $subDirs, $pluginName = '')
    {
        $policyName = class_basename($className) . 'Policy';
        $this->call('make:custom:policy', [
            'name' => $policyName,
            '--model' => $this->qualifyModel($className, $fileType, $subDirs, $pluginName),
        ]);
    }


    /**
     * Build the replacements for a factory
     */
    protected function buildFactoryReplacements($className, $fileType, $subDirs, $pluginName = '')
    {
        $replacements = [];

        if ($this->option('factory') || $this->option('all')) {
            $modelPath = $this->qualifyModel($className, $fileType, $subDirs, $pluginName);
            $factoryNamespace = 'Database\\Factories\\' . class_basename($className) . 'Factory';

            $factoryCode = <<<EOT
            /** @use HasFactory<{$factoryNamespace}> */
                use HasFactory;
            EOT;

            $replacements['factory'] = $factoryCode;
            $replacements['factoryImport'] = 'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;';
        } else {
            $replacements['factory'] = '//';
            $replacements['factoryImport'] = '';
        }

        return $replacements;
    }

    /**
     * Qualify the given model class base name
     */
    protected function qualifyModel($className, $fileType, $subDirs, $pluginName = '')
    {
        $namespace = $this->getNamespace($fileType, $pluginName, 'model', $subDirs);
        return $namespace . '\\' . $className;
    }
}
