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
use App\Console\Traits\MakeNotificationTrait;

class MakeCustomNotification extends Command
{
    use MakeNotificationTrait;

    protected $signature = 'make:custom:notification
        {name : The notification class (with optional subfolders, e.g. Admin/SendUpdate)}
        {--force : Overwrite if notification already exists}';

    protected $description = 'Create a new notification in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        $force = (bool)$this->option('force');

        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    protected function getNotificationDirectory(array $subDirs): string
    {
        $base = base_path('custom/notifications');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getNotificationNamespace(array $subDirs): string
    {
        $base = 'Custom\\Notifications';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
