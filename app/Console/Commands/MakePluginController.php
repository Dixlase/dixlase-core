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
use App\Console\Traits\MakeControllerTrait;
use App\Console\Traits\PluginManagementTrait;
use App\Console\Traits\MakeLicenseTrait;

class MakePluginController extends Command
{
    use MakeControllerTrait;
    use PluginManagementTrait;
    use MakeLicenseTrait;



    /**
     * Artisan コマンド名と引数/オプション定義
     * 例: php artisan make:plugin:controller my-plugin MyController
     */
    protected $signature = 'make:plugin:controller
        {plugin : The plugin name}
        {name : The controller name}
        {--scope=plain}
        {--force}
        {--invokable}
        {--model=}
        {--parent=}
        {--resource}
        {--requests}
        {--api}
        {--singleton}
        {--creatable}
        {--type=}';

    protected $description = 'Create a new controller for the specified plugin';

    protected string $controllerRootType = 'Plugins';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $path      = str_replace('\\', '/', $this->argument('name'));
        $parts     = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $this->applyScopeToSubDirs($parts);
        $pluginName = Str::studly($this->argument('plugin'));

        // ✅ `PluginLicenseTrait` を使ってライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);



        if (empty($licenseInfo)) {
            $this->error('Plugin license information not found.');
            return 1;
        }

        $options = [
            'scope'     => $this->option('scope'),
            'force'     => $this->option('force'),
            'invokable' => $this->option('invokable'),
            'model'     => $this->option('model'),
            'parent'    => $this->option('parent'),
            'resource'  => $this->option('resource'),
            'requests'  => $this->option('requests'),
            'api'       => $this->option('api'),
            'singleton' => $this->option('singleton'),
            'creatable' => $this->option('creatable'),
            'type'      => $this->option('type'),
        ];

        $this->makeFile($className, $subDirs, $options, 'Plugins', $pluginName, $licenseInfo);

        return 0;
    }

    protected function getControllerRootName(): string
    {
        return Str::studly($this->argument('plugin'));
    }
}
