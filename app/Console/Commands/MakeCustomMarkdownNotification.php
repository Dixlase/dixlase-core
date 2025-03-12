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
use App\Services\FileGenerator;
use App\Console\Traits\MakeMarkdownNotificationTrait;

class MakeCustomMarkdownNotification extends Command
{
    use MakeMarkdownNotificationTrait;

    protected $signature = 'make:custom:markdown-notification
        {name : The notification class name (e.g. Admin/NewMarkdownNotification)}
        {--view=notifications.example : The Markdown Blade view name}
        {--force : Overwrite if the class already exists}';

    protected $description = 'Create a new Markdown-based notification class in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --view
        $view = $this->option('view') ?: 'notifications.example';

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $view);

        return 0;
    }

    /**
     * (B)パターン: getMarkdownNotificationDirectory/Namespace
     */
    protected function getMarkdownNotificationDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Notifications');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getMarkdownNotificationNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Notifications';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
