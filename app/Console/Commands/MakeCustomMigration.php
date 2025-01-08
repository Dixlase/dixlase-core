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

class MakeCustomMigration extends GeneratorCommand
{
    protected $signature = 'make:custom-migration {name}';
    protected $description = 'Create a new migration in the custom directory';

    protected function getStub()
    {
        return base_path('/stubs/migration.stub');  // マイグレーションのスタブ（オプション）
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Custom\Database\Migrations';  // custom/database/migrations に作成
    }

    protected function buildClass($name)
    {
        // カスタム名前空間から不要な部分を削除
        $name = str_replace('Custom\\Database\\Migrations', '', $name);
        return parent::buildClass($name);
    }

    protected function getMigrationPath()
    {
        // マイグレーションのパスをcustomディレクトリに変更
        return base_path('custom/database/migrations');
    }
}
