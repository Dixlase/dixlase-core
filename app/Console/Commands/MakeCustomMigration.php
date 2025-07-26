<?php

/**
 * This file is part of Dixlase.
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
use App\Console\Traits\MakeMigrationTrait;
use App\Console\Traits\MakeCustomCommandTrait;

class MakeCustomMigration extends Command
{
    use MakeMigrationTrait;
    use MakeCustomCommandTrait;

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
        $this->signature = $this->makeSignature(
            'make:custom:migration'
            .$this->getCustomCommandSignature(),
            $this->getAdditionalOptions()
        );
        
        $this->setDescription(__('command.make_custom.migration.description'));
        
        parent::__construct();
    }

    public function handle()
    {
        return $this->generateCustomFile(
            $this->argument('className'),
            $this->argument('fileType'),
            $this->argument('pluginName'),
            'migration',
            $this->options()
        );
    }
}
