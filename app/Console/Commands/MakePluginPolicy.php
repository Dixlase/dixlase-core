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
use App\Console\Traits\MakePolicyTrait;

class MakePluginPolicy extends Command
{
    use MakePolicyTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:policy
        {plugin : The plugin name}
        {name : The policy class name (with optional subfolders, e.g. Admin/UserPolicy)}
        {--force : Overwrite if the policy already exists}
        {--model= : The model that the policy applies to}
        {--guard= : The guard that the policy relies on (not fully implemented in this example)}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class for the specified plugin.';

    /**
     * Execute the console command.
     */

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse plugin, policy name
        $pluginName = Str::studly($this->argument('plugin'));
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force, --model
        $force = (bool)$this->option('force');
        $modelOpt = $this->option('model');

        // 3) create file via trait
        $this->makeFile($className, $subDirs, $force, $modelOpt);

        // 4) 追加でプレースホルダ (modelFQCN, userFQCN) を埋め込む場合、2段階置換 or
        //    you can do it by rewriting "makeFiler" with more placeholders
        //    ここでは例として "postReplacePolicy(...)" のように書いてファイルをリ-read/write してもいいが
        //    まとめてやりたいなら "MakePolicyTrait" 内部をオーバーライドする方法でもOK

        // For simplicity, we do the final replacements in the generated file:
        $this->postProcessPolicyFile($pluginName, $className, $modelOpt);

        return 0;
    }

    /**
     * 2段階置換: 生成後にファイルを再読み込みして modelFQCN, userFQCN を置換
     */
    protected function postProcessPolicyFile(string $pluginName, string $className, ?string $modelOption)
    {
        $filePath = $this->getPolicyDirectory([]) . "/{$className}.php";
        if (! file_exists($filePath)) {
            return;
        }

        $contents = file_get_contents($filePath);

        // modelFQCN
        $modelFqcn = $modelOption
            ? $this->qualifyModel($modelOption, $pluginName)
            : 'App\\Models\\SomeModel';
        $modelBase = class_basename($modelFqcn);
        $modelVar  = Str::camel($modelBase);

        // userFQCN
        $userFqcn = $this->qualifyUserModel(); // "App\\Models\\User"
        $userBase = class_basename($userFqcn);

        $search = [
            '{{ namespacedModel }}',
            '{{ model }}',
            '{{ modelVariable }}',
            '{{ namespacedUserModel }}',
            '{{ user }}',
        ];
        $replace = [
            $modelFqcn,
            $modelBase,
            $modelVar,
            $userFqcn,
            $userBase,
        ];

        $contents = str_replace($search, $replace, $contents);

        file_put_contents($filePath, $contents);
    }

    protected function qualifyModel(string $modelOption, string $pluginName): string
    {
        if (Str::startsWith($modelOption, '\\')) {
            $modelOption = Str::replaceFirst('\\', '', $modelOption);
        }
        if (Str::contains($modelOption, '\\')) {
            return $modelOption;
        }
        return "Plugins\\{$pluginName}\\App\\Models\\{$modelOption}";
    }

    protected function qualifyUserModel(): string
    {
        // simplify
        return 'App\\Models\\User';
    }

    // (B)パターン: getPolicyDirectory(), getPolicyNamespace()
    protected function getPolicyDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Policies");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getPolicyNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Policies";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
