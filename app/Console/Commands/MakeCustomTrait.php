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

use Illuminate\Console\GeneratorCommand;

class MakeCustomTrait extends GeneratorCommand
{
    protected $signature = 'make:custom-trait {name}';
    protected $description = 'Create a new trait in the custom directory';

    protected function getStub()
    {
        return base_path('/stubs/trait.stub');  // トレイツタブ（オプション）
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Custom\Traits';  // custom/traits に作成
    }

    protected function buildClass($name)
    {
        $name = str_replace('Custom\\Traits', '', $name);
        return parent::buildClass($name);
    }
}
