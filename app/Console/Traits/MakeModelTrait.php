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
            '{--policy : ' . __("command.make.options.policy") . '}',
            '{--seed : ' . __("command.make.options.seed") . '}',
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
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName = '',
        array $licenseInfo = []
    ): void {
        // モデル用 stubの選択
        $stubFile = $this->renderStub($options['scope'] ?? 'plain', $options);

        // ファイル生成
        $this->makeFiler(
            $className,
            $fileType,
            'models',
            $options,
            $subDirs,
            $stubFile,
            $pluginName,
            [],  // プレースホルダー
            $licenseInfo
        );
    }


    /**
     * モデル用のスタブを選択して取得
     *
     * @param  string  $scope
     * @param  array   $options
     * @return string
     */
    protected function renderStub(string $scope = 'plain', array $options = []): string
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
