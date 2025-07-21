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
use App\Console\Traits\MakePluginCommandTrait;


class MakePluginMigration extends Command
{

    use MakeMigrationTrait;
    use MakePluginCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Create a new migration file in the specified plugin directory';


    public function __construct()
    {
        $this->signature = $this->makeSignature('make:plugin:migration
            {className? : The name of the migration (e.g., User)}
            {pluginName? : The name of the plugin}',
            $this->getAdditionalOptions()
        );

        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $options = $this->options();
        // --create または --table オプションが指定されていたら追加
        if ($this->hasOption('create') && $this->option('create') !== null) {
            $options['create'] = $this->option('create');
        } elseif ($this->hasOption('table') && $this->option('table') !== null) {
            $options['table'] = $this->option('table');
        }

        return $this->generatePluginFile(
            $this->argument('className'),
            $this->argument('pluginName'),
            'migration',
            $options,
        );

    }

}
