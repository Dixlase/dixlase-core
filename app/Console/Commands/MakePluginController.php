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
use Illuminate\Support\Facades\File;
use App\Console\Traits\MakeControllerTrait;
use App\Console\Traits\PluginManagementTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;

class MakePluginController extends Command
{
    use MakeControllerTrait;
    use PluginManagementTrait;
    use MakeLicenseTrait;
    use MakeFileTrait;

    /**
     * Artisan コマンド名と引数/オプション定義
     * 例: php artisan make:plugin:controller my-plugin MyController
     */
    protected $signature = 'make:plugin:controller
        {className : The controller name}
        {--force}
        {--invokable}
        {--model=}
        {--parent=}
        {--resource}
        {--requests}
        {--api}
        {--singleton}
        {--creatable}';

    protected $description = 'Create a new controller for the specified plugin';

    protected string $controllerRootType = 'Plugins';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $className = Str::studly($this->argument('className'));

        // 🔽 pluginsディレクトリ内の一覧を取得
        $pluginDirs = collect(File::directories(base_path('plugins')))
            ->map(fn($dir) => basename($dir))
            ->filter()
            ->values()
            ->all();

        if (empty($pluginDirs)) {
            $this->error('プラグインが見つかりません。plugins ディレクトリに少なくとも1つのプラグインが必要です。');
            return 1;
        }

        // 🔽 選択式でプラグイン名を指定
        $pluginName = $this->choice('プラグインを選択してください', $pluginDirs);

        // 🔽 選択式でスコープを指定
        $scopes = [
            '1' => 'スコープなし',
            '2' => 'フロント用',
            '3' => '管理画面用',
        ];

        $scopeMap = [
            'スコープなし' => 'plain',
            'フロント用' => 'front',
            '管理画面用' => 'admin',
        ];

        $scopeKey = $this->choice('スコープを選択してください', $scopes);

        $this->info("選択されたスコープ: {$scopeKey}");

        // 数字を key にしてマップ
        $scope = $scopeMap[$scopeKey] ?? 'plain';
        $path      = str_replace('\\', '/', $this->argument('className'));
        $parts     = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $parts;
        $pluginName = Str::studly($pluginName);

        // MakeLicenseTraitを使ってプラグインのライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);

        $options = [
            'scope'     => $scope,
            'force'     => $this->option('force'),
            'invokable' => $this->option('invokable'),
            'model'     => $this->option('model'),
            'parent'    => $this->option('parent'),
            'resource'  => $this->option('resource'),
            'requests'  => $this->option('requests'),
            'api'       => $this->option('api'),
            'singleton' => $this->option('singleton'),
            'creatable' => $this->option('creatable'),
        ];

        $this->makeFile(
            className: $className,
            fileType: 'plugins',
            options: $options,
            subDirs: $subDirs,
            pluginName: $pluginName,
            licenseInfo: $licenseInfo
        );

        return Command::SUCCESS;
    }
}
