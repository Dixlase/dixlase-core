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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeMigrationTrait;

class MakePluginMigration extends Command
{

    use MakeMigrationTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:migration
        {plugin : The name of the plugin (e.g. "EventsPlugin")}
        {name : The migration name (e.g. "create_events_table")}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the file should be created}
        {--realpath : Indicate that the provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Create a new migration file in the specified plugin directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName   = Str::studly($this->argument('plugin'));
        $migrationName = $this->argument('name');

        $createOption = $this->option('create');
        $tableOption  = $this->option('table');
        $customPath   = $this->option('path');
        $realpathOpt  = (bool)$this->option('realpath');
        $fullpathOpt  = (bool)$this->option('fullpath');
        $forceOpt     = (bool)$this->option('force');

        // MakeMigrationTrait::makeMigration(...)
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

    /**
     * (B)パターン: getMigrationDirectory() / getMigrationNamespace()
     */
    protected function getMigrationDirectory(): string
    {
        // 通常 "plugins/PluginName/database/migrations"
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/database/migrations");
    }

    protected function getMigrationNamespace(): string
    {
        // マイグレーションに namespace を設定したいなら
        // "Plugins\{Plugin}\Database\Migrations" とする等。
        $pluginName = Str::studly($this->argument('plugin'));
        return "Plugins\\{$pluginName}\\Database\\Migrations";
    }
}
