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
use App\Console\Traits\MakePolicyTrait;
use App\Console\Traits\MakePluginCommandTrait;
use App\Console\Traits\MakeLicenseTrait;

class MakePluginPolicy extends Command
{
    use MakePolicyTrait;
    use MakePluginCommandTrait;
    use MakeLicenseTrait;



    public function __construct()
    {
        $this->signature = $this->makeSignature(
            'make:plugin:policy ' . 
            $this->getPluginCommandSignature(true),
            $this->getAdditionalOptions()
        );
        $this->setDescription(__('command.make_plugin.policy.description'));
        parent::__construct();
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
            'policy',
            $this->options()
        );
    }


}
