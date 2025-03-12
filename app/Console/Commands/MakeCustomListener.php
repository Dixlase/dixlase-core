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
use App\Console\Traits\MakeListenerTrait;

class MakeCustomListener extends Command
{
    use MakeListenerTrait;

    protected $signature = 'make:custom:listener
        {name : The listener class (with optional subfolders, e.g. Admin/MyListener)}
        {--force : Overwrite if listener already exists}
        {--event= : The event class being listened for}
        {--queued : Indicates the event listener should be queued}';

    protected $description = 'Create a new event listener in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force, --event, --queued
        $force    = (bool) $this->option('force');
        $queued   = (bool) $this->option('queued');
        $eventOpt = $this->option('event');

        // イベントクラスFQCN (カスタムディレクトリの場合、FQCNをどうするかは自由)
        $eventClass = $eventOpt ? $this->qualifyEventClass($eventOpt) : null;

        $this->makeFile($className, $subDirs, $force, $queued, $eventClass);

        return 0;
    }

    /**
     * イベントクラスをFQCNに変換
     * (カスタム用のルールがあれば適宜)
     */
    protected function qualifyEventClass(string $eventOption): string
    {
        if (Str::startsWith($eventOption, '\\')) {
            $eventOption = Str::replaceFirst('\\', '', $eventOption);
        }
        // 既に FQCN -> そのまま
        if (Str::contains($eventOption, '\\')) {
            return $eventOption;
        }
        // カスタムなら 'Custom\App\Events\...'? あるいは 'App\Events\...'
        return 'App\\Events\\' . $eventOption;
    }

    protected function getListenerDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Listeners');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getListenerNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Listeners';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
