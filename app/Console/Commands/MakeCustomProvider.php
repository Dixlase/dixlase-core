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
use App\Console\Traits\ChoiceLicenseTrait;

class MakeCustomProvider extends Command
{
    use MakeProviderTrait;
    use ChoiceLicenseTrait;


    protected $signature = 'make:custom:provider
        {name : The name of the service provider (with optional subfolders, e.g. Admin/MyServiceProvider)}
        {--license= : Specify the license (e.g. gpl, mit, apache)}
        {--force : Overwrite if provider already exists}';


    protected $description = 'Create a new service provider in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // クラス名とサブディレクトリを取得
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) オプションの取得
        $force              = (bool) $this->option('force');
        $licenseOption      = $this->option('license') ?? 'gpl'; // デフォルトは GPL-3.0

        // ライセンス情報の取得
        $licenseInfo = $this->getLicenseInfo($licenseOption);
        if (!$licenseInfo) {
            $this->error("ライセンス情報が取得できませんでした。処理を中止します。");
            return Command::FAILURE;
        }

        // `MakeProviderTrait` を使用してファイル作成
        $this->makeFile($className, $subDirs, $force, false, $licenseInfo);

        return 0;
    }

    /**
     * 例: addProviderToBootstrap()
     *  Laravel 11+ にある ServiceProvider::addProviderToBootstrapFile() 相当を使うならこんな形
     */
    protected function addProviderToBootstrap(string $className, array $subDirs): void
    {
        $qualifiedClass = $this->getProviderNamespace($subDirs) . '\\' . $className;
        // e.g. serviceProvider::addProviderToBootstrapFile($qualifiedClass, base_path('bootstrap/providers.php'))
        // ... 省略
    }

    /**
     * (B)パターン: getProviderDirectory, getProviderNamespace
     */
    protected function getProviderDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Providers');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getProviderNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Providers';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
