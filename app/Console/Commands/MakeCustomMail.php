<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
use App\Console\Traits\MakeMailTrait;

class MakeCustomMail extends Command
{
    use MakeMailTrait;

    protected $signature = 'make:custom:mail
        {name : The mailable class name (optionally with subfolders, e.g. Admin/MyMail)}
        {--markdown : Indicates whether to create a Markdown-based Mailable}
        {--subject=Mail Subject : The email subject}
        {--view=view.name : The Blade view name (used if --markdown is present)}
        {--force : Overwrite if the Mailable class already exists}';

    protected $description = 'Create a new Mailable class in the custom directory';

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

        // 2) --markdown, --subject, --view
        $isMarkdown = (bool)$this->option('markdown');
        $subject    = $this->option('subject') ?: 'Mail Subject';
        $view       = $this->option('view')    ?: 'view.name';

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $isMarkdown, $subject, $view);

        return 0;
    }

    /**
     * (B)パターン: getMailDirectory/Namespace
     */
    protected function getMailDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Mail');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getMailNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Mail';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
