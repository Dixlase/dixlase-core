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
use App\Console\Traits\MakeRequestTrait;
use App\Console\Traits\MakeCustomCommandTrait;

class MakeCustomRequest extends Command
{
    use MakeRequestTrait;
    use MakeCustomCommandTrait;

    public function __construct(FileGenerator $fileGenerator)
    {
        $this->signature = $this->makeSignature(
            'make:custom:request'
            .$this->getCustomCommandSignature(),
            $this->getAdditionalOptions()
        );
        
        $this->setDescription(__('command.make_custom.request.description'));
        
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        return $this->generateCustomFile(
            $this->argument('className'),
            $this->argument('fileType'),
            $this->argument('pluginName'),
            'request',
            $this->options()
        );
    }
}
