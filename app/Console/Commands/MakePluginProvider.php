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
use App\Console\Traits\MakePluginCommandTrait;
use App\Console\Traits\MakeLicenseTrait;
use Illuminate\Support\ServiceProvider;

class MakePluginProvider extends Command
{
    use MakeProviderTrait;
    use MakePluginCommandTrait;
    use MakeLicenseTrait;

    /**
     * コンソールコマンドのシグネチャ
     *
     * @var string
     */
    protected $signature;

    /**
     * コンソールコマンドの名前
     *
     * @var string
     */
    protected $name = 'make:plugin:provider';

    /**
     * コンソールコマンドの説明
     *
     * @var string
     */
    protected $description = 'プラグイン用の新しいサービスプロバイダを作成します';

    /**
     * 生成するクラスのタイプ
     *
     * @var string
     */
    protected $type = 'Provider';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->signature = $this->makeSignature($this->name, $this->getAdditionalOptions());
        parent::__construct();
    }

    /**
     * コンソールコマンドを実行します。
     *
     * @return int
     */
    public function handle()
    {
        // 共通の初期化処理を実行
        $commonInit = $this->initializePluginCommand($this->argument('className'));
        if (!$commonInit) {
            return Command::FAILURE;
        }

        // ファイルを生成
        return $this->makePluginFile($commonInit, $this->collectOptions())
            ? Command::SUCCESS
            : Command::FAILURE;
    }


    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\App\\Providers';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * プロバイダーのディレクトリパスを取得します。
     *
     * @param  array  $subDirs サブディレクトリの配列
     * @return string ディレクトリパス
     */
    protected function getProviderDirectory(array $subDirs): string
    {
        return $this->getDirectory($subDirs);
    }

    /**
     * プロバイダーの名前空間を取得します。
     *
     * @param  array  $subDirs サブディレクトリの配列
     * @return string 名前空間
     */
    protected function getProviderNamespace(array $subDirs): string
    {
        return $this->getNamespace($subDirs);
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

    /**
     * (B)パターン: getProviderDirectory/Namespace
     */

     /*
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
        */
}
