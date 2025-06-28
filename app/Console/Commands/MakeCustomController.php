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
use Illuminate\Support\Facades\File;
use App\Console\Traits\MakeControllerTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeCustomCommandTrait;

class MakeCustomController extends Command
{
    use MakeControllerTrait;
    use MakeLicenseTrait;
    use MakeCustomCommandTrait;

    protected $signature;

    public function __construct()
    {
        $this->signature = $this->makeSignature('make:custom:controller', $this->getAdditionalOptions());
        parent::__construct();
    }

    public function handle()
    {
        // 共通の初期化処理
        $common = $this->initializeCustomCommand($this->argument('className'));
        if (!$common) {
            return Command::FAILURE;
        }

        

        // ファイル生成
        return $this->makeCustomFile($common, $this->option())
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
