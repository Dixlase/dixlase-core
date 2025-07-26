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
use App\Services\FileGenerator;
use App\Console\Traits\MakeMailTrait;

class MakePluginMail extends Command
{
    use MakeMailTrait;

    protected $signature = 'make:plugin:mail
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The mailable class name (e.g. Admin/MyMail)}
        {--markdown : Indicates whether to create a Markdown-based Mailable}
        {--subject=Mail Subject : The email subject}
        {--view=view.name : The Blade (markdown) view name}
        {--force : Overwrite if the Mailable already exists}';

    protected $description = 'Create a new Mailable class in the specified plugin directory';

    protected FileGenerator $fileGenerator;

    protected string $pluginName;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin name
        $this->pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) options
        $isMarkdown = (bool)$this->option('markdown');
        $subject    = $this->option('subject') ?: 'Mail Subject';
        $view       = $this->option('view')    ?: 'view.name';
        $force      = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $isMarkdown, $subject, $view);

        return 0;
    }

    /**
     * (B)パターン: getMailDirectory/Namespace
     */
    protected function getMailDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Mail"
        $base = base_path("plugins/{$this->pluginName}/app/Mail");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getMailNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Mail"
        $base = "Plugins\\{$this->pluginName}\\App\\Mail";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
