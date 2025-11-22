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
use App\Console\Traits\MakeVoltTrait;
use App\Console\Traits\MakeThemeCommandTrait;
use App\Console\Traits\MakeLicenseTrait;

class MakeThemeVolt extends Command
{
    use MakeVoltTrait;
    use MakeThemeCommandTrait;
    use MakeLicenseTrait;

    public function __construct()
    {
        $this->signature = $this->makeSignature(
            'make:theme:volt ' . $this->getThemeCommandSignature(),
            $this->getAdditionalOptions()
        );
        $this->setDescription(__('command.make_theme.volt.description'));
        parent::__construct();
    }

    public function handle()
    {
        return $this->generateThemeFile(
            $this->argument('className'),
            $this->argument('themeName'),
            'volt',
            $this->options()
        );
    }
}
