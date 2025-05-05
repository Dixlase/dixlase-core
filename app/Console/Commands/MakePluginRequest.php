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
use App\Console\Traits\MakeLicenseTrait;


class MakePluginRequest extends Command
{
    use MakeRequestTrait;

    protected $signature = 'make:plugin:request
        {plugin : The plugin name (e.g. "MyPlugin")}
        {name : The FormRequest class name (with optional subfolders, e.g. Admin/StoreDataRequest)}
        {--force : Overwrite if the request already exists}';

    protected $description = 'Create a new FormRequest class for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool)$this->option('force');

        // プラグインのライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);
        if (!$licenseInfo) {
            return Command::FAILURE;
        }

        // 4) Traitの makeFile(...) 呼び出し
        $this->makeFile($className, $subDirs, $force, $licenseInfo);

        $this->info("プラグイン [{$pluginName}] のリクエストファイル [{$className}] を作成しました。");
        return Command::SUCCESS;
    }

    /**
     * (B)パターンで getRequestDirectory/Namespace
     */
    protected function getRequestDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Http/Requests");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getRequestNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Http\\Requests";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
