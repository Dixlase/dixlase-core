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
use App\Console\Traits\MakeProviderTrait;
use App\Console\Traits\PluginManagementTrait;
use App\Console\Traits\MakeLicenseTrait;
use Illuminate\Support\ServiceProvider;

class MakePluginProvider extends Command
{

    use MakeProviderTrait;
    use PluginManagementTrait;
    use MakeLicenseTrait;


    protected $signature = 'make:plugin:provider
        {plugin : The plugin name (e.g. "MyPlugin")}
        {name : The name of the service provider (e.g. "MyPluginServiceProvider")}
        {--plugin : Use the plugin-specific provider template (provider.plugin.stub)}
        {--force : Overwrite if provider already exists}';

    protected $description = 'Create a new service provider for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin & provider
        $pluginName = $this->argument('plugin'); // e.g. "MyPlugin"
        $className   = $this->argument('name');   // e.g. "MyPluginServiceProvider"
        $force       = (bool) $this->option('force');

        // ✅ `PluginLicenseTrait` を使ってライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);
        if (!$licenseInfo) {
            return Command::FAILURE; // ライセンスが取得できなかったら処理を中止
        }


        // 2) parseClassName → subDirs + finalClass
        //    もし "Admin/MyProvider" のようにsubDirsを使うなら:
        [$subDirs, $finalClass] = $this->fileGenerator->parseClassName($className);

        // 3) Traitの makeFile
        //    => (className, subDirs, force, pluginStub)
        $this->makeFile($finalClass, $subDirs, $force, true, $licenseInfo);

        // 4) addProviderToBootstrapFile (Laravel 11+ オプション)
        $this->addProviderToBootstrap($pluginName, $subDirs, $finalClass);

        return 0;
    }

    /**
     * ServiceProvider::addProviderToBootstrapFile()を使って
     * "bootstrap/providers.php" にプロバイダを登録したい場合
     */
    protected function addProviderToBootstrap(
        string $pluginName,
        array $subDirs,
        string $className
    ): void {
        // "Plugins\MyPlugin\App\Providers" + subDirs
        $providerNamespace = $this->getProviderNamespace($subDirs);
        $qualifiedClass = $providerNamespace . '\\' . $className;

        try {
            if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
                // base_path('bootstrap/providers.php') 等
                $filePath = base_path('bootstrap/providers.php');

                ServiceProvider::addProviderToBootstrapFile($qualifiedClass, $filePath);
                $this->info("Added [{$qualifiedClass}] to [{$filePath}].");
            }
        } catch (\Throwable $ex) {
            $this->warn("Unable to add provider to bootstrap file: {$ex->getMessage()}");
        }
    }

    /**
     * (B)パターン: getProviderDirectory/Namespace
     */
    protected function getProviderDirectory(array $subDirs): string
    {
        $plugin = $this->argument('plugin');
        $base = base_path("plugins/{$plugin}/app/Providers");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getProviderNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\App\\Providers";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
