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
use App\Console\Traits\MakeProviderTrait;
use App\Console\Traits\MakePluginCommandTrait;
use App\Console\Traits\MakeLicenseTrait;
use Illuminate\Support\ServiceProvider;

class MakePluginProvider extends Command
{
    use MakeProviderTrait;
    use MakePluginCommandTrait;
    use MakeLicenseTrait;

    public function __construct()
    {
        $this->signature = $this->makeSignature(
            'dls:make:plugin:provider '
            .$this->getPluginCommandSignature(true),
            $this->getAdditionalOptions()
        );

        $this->setDescription(__('command.make_plugin.provider.description'));
        
        parent::__construct();
    }

    /**
     * コンソールコマンドを実行します。
     *
     * @return int
     */
    public function handle()
    {
        return $this->generatePluginFile(
            $this->argument('className'),
            $this->argument('pluginName'),
            'provider',
            $this->options()
        );
    }

    /**
     * プロバイダーをブートストラップファイルに追加します。
     *
     * @param  string  $pluginName プラグイン名
     * @param  array   $subDirs    サブディレクトリの配列
     * @param  string  $className  クラス名
     * @return void
     */
    protected function addProviderToBootstrap(string $pluginName, array $subDirs, string $className): void
    {
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

}
