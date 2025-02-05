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
use App\Console\Traits\MakeMigrationTrait;

class MakeCustomMigration extends Command
{
    use MakeMigrationTrait;

    protected $signature = 'make:custom:migration
        {name : The migration name (e.g. "create_custom_table")}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the file should be created}
        {--realpath : Indicate that the provided migration file paths are absolute}
        {--fullpath : Output the full path of the migration}
        {--force : Force the operation to run when in production}';

    protected $description = 'Create a new migration in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $migrationName = $this->argument('name');
        $createOption  = $this->option('create');
        $tableOption   = $this->option('table');
        $customPath    = $this->option('path');
        $realpathOpt   = (bool)$this->option('realpath');
        $fullpathOpt   = (bool)$this->option('fullpath');
        $forceOpt      = (bool)$this->option('force');

        $this->makeMigration(
            $migrationName,
            $forceOpt,
            $createOption,
            $tableOption,
            $customPath,
            $realpathOpt,
            $fullpathOpt
        );

        return 0;
    }

    protected function getMigrationDirectory(): string
    {
        // custom/database/migrations
        return base_path('custom/database/migrations');
    }

    protected function getMigrationNamespace(): string
    {
        // 任意の namespace
        return 'Custom\\Database\\Migrations';
    }
}
