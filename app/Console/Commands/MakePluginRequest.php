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
use App\Console\Traits\MakeRequestTrait;
use App\Console\Traits\MakePluginCommandTrait;
use App\Console\Traits\MakeLicenseTrait;

class MakePluginRequest extends Command
{
    use MakeRequestTrait;
    use MakePluginCommandTrait;
    use MakeLicenseTrait;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new FormRequest class for the specified plugin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->signature = $this->makeSignature(
            'make:plugin:request
            {className?}
            {pluginName?}',
            $this->getAdditionalOptions()
        );
        
        parent::__construct($this->signature);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->generatePluginFile(
            $this->argument('className'),
            $this->argument('pluginName'),
            'request',
            $this->options()
        );
    }
}
