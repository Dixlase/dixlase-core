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
use App\Console\Traits\MakeEventTrait;

class MakeCustomEvent extends Command
{
    use MakeEventTrait;

    /**
     * コマンド名と引数・オプション定義
     */
    protected $signature = 'make:custom:event
        {name : The name of the event class (with optional subfolders, e.g. Admin/MyEvent)}
        {--force : Create the class even if the event already exists}';

    protected $description = 'Create a new event in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parseEventName のかわりに parseClassName を呼ぶ
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        $force = (bool) $this->option('force');

        // 2) $this->makeFile($className, $subDirs, $force) など
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getDirectory(array $subDirs)/getNamespace(array $subDirs)で
     * getEventDirectory(), getEventNamespace() を呼び出すようにしているので
     * ここで実装する
     */
    protected function getEventDirectory(array $subDirs): string
    {
        $basePath = base_path('custom/app/Events');
        if ($subDirs) {
            $basePath .= '/' . implode('/', $subDirs);
        }
        return $basePath;
    }

    protected function getEventNamespace(array $subDirs): string
    {
        $baseNs = 'Custom\\App\\Events';
        if ($subDirs) {
            $baseNs .= '\\' . implode('\\', $subDirs);
        }
        return $baseNs;
    }
}
