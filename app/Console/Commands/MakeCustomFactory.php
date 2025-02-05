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
use App\Console\Traits\MakeFactoryTrait;

class MakeCustomFactory extends Command
{
    use MakeFactoryTrait;

    protected $signature = 'make:custom:factory
        {name : The factory class name (e.g. UserFactory or just User)}
        {--model= : The model class the factory applies to (FQCN or relative)}
        {--force : Overwrite the factory if it already exists}';

    protected $description = 'Create a new model factory in the custom directory (custom/database/factories).';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $className = $this->argument('name');
        if (! Str::endsWith($className, 'Factory')) {
            $className .= 'Factory';
        }

        $model = $this->option('model');
        $force = (bool) $this->option('force');

        // MakeFactoryTrait::makeFile(...) を呼ぶ
        $this->makeFile($className, $model, $force);

        return 0;
    }

    /**
     * サブクラスで実装: getFactoryDirectory(), getFactoryNamespace()
     */
    protected function getFactoryDirectory(): string
    {
        return base_path('custom/database/factories');
    }

    protected function getFactoryNamespace(): string
    {
        return 'Custom\\Database\\Factories';
    }
}
