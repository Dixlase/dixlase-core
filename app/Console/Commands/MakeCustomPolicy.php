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

class MakeCustomPolicy extends Command
{
    use MakePolicyTrait;

    protected $signature = 'make:custom:policy
        {name : The policy class name (with optional subfolders, e.g. Admin/UserPolicy)}
        {--force : Overwrite if policy already exists}
        {--model= : The model that the policy applies to}';

    protected $description = 'Create a new policy in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        $force   = (bool) $this->option('force');
        $modelOpt = $this->option('model');

        $this->makeFile($className, $subDirs, $force, $modelOpt);

        $this->postProcessPolicyFile($className, $modelOpt);

        return 0;
    }

    protected function postProcessPolicyFile(string $className, ?string $modelOption)
    {
        $filePath = $this->getPolicyDirectory([]) . "/{$className}.php";
        if (! file_exists($filePath)) {
            return;
        }

        $contents = file_get_contents($filePath);

        $modelFqcn = $modelOption
            ? $this->qualifyModel($modelOption)
            : 'App\\Models\\SomeModel';
        $modelBase = class_basename($modelFqcn);
        $modelVar  = Str::camel($modelBase);

        $userFqcn  = $this->qualifyUserModel();
        $userBase  = class_basename($userFqcn);

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

    protected function qualifyModel(string $modelOption): string
    {
        if (Str::startsWith($modelOption, '\\')) {
            $modelOption = Str::replaceFirst('\\', '', $modelOption);
        }
        if (Str::contains($modelOption, '\\')) {
            return $modelOption;
        }
        // customの場合 => "Custom\\App\\Models\\{$modelOption}" など適当
        return "App\\Models\\{$modelOption}";
    }

    protected function qualifyUserModel(): string
    {
        return 'App\\Models\\User';
    }

    protected function getPolicyDirectory(array $subDirs): string
    {
        $base = base_path('custom/policies');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getPolicyNamespace(array $subDirs): string
    {
        $base = 'Custom\\Policies';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
