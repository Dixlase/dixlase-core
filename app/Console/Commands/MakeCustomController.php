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

class MakeCustomController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:custom-model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model in the custom directory';

    protected function getStub()
    {
        return base_path('/stubs/model.stub');  // モデルのスタブ（オプション）
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Custom\App';  // custom/app ディレクトリ内に作成
    }

    /**
     * Execute the console command.
     */
    protected function buildClass($name)
    {
        $name = str_replace('Custom\\App', '', $name);
        return parent::buildClass($name);
    }
}
