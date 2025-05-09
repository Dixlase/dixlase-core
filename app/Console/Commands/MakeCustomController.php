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
use App\Console\Traits\MakeLicenseTrait;

class MakeCustomController extends Command
{
    use MakeControllerTrait;
    use MakeLicenseTrait;

    /**
     * Artisan コマンド名と引数/オプション定義
     */
    protected $signature = 'make:custom:controller
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


    protected $description = 'Create a new controller in the custom directory';

    protected string $controllerRootType = 'Custom';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        // クラス名を取得
        $className = Str::studly($this->argument('className'));

        // ファイルタイプ選択
        $fileTypeMap = [
            'コアファイル（core）' => 'core',
            'プラグインファイル（plugin）' => 'plugin',
        ];
        $fileTypeKey = $this->choice('ファイルの種類を選択してください', [
            '1' => 'コアファイル（core）',
            '2' => 'プラグインファイル（plugin）',
        ]);
        $fileType = $fileTypeMap[$fileTypeKey];


        $this->info($fileType);

        // プラグイン名取得（plugin の場合）
        $pluginName = 'Core';
        if ($fileType === 'plugin') {
            $pluginDirs = collect(File::directories(base_path('plugins')))
                ->map(fn($dir) => basename($dir))
                ->filter()
                ->values()
                ->all();

            if (empty($pluginDirs)) {
                $this->error('プラグインが見つかりません。plugins ディレクトリに少なくとも1つのプラグインが必要です。');
                return Command::FAILURE;
            }

            $pluginName = $this->choice('プラグインを選択してください', $pluginDirs);
        }


        // スコープ選択
        $scopes = [
            '1' => 'スコープなし（plain）',
            '2' => 'フロント用（front）',
            '3' => '管理画面用（admin）',
        ];
        $scopeMap = [
            'スコープなし（plain）' => 'plain',
            'フロント用（front）' => 'front',
            '管理画面用（admin）' => 'admin',
        ];
        $scopeKey = $this->choice('スコープを選択してください', $scopes);
        $scope = $scopeMap[$scopeKey] ?? 'plain';

        // ライセンス情報
        $licenseInfo = null;
        if ($fileType === 'plugin') {
            $licenseInfo = $this->getPluginLicenseInfo($pluginName);
        } else {
            $licenseInfo = $this->getCoreLicenseInfo();
        }

        // スコープを適用したサブディレクトリを取得
        $scope = $scopeMap[$scopeKey] ?? 'plain';
        $path      = str_replace('\\', '/', $this->argument('className'));
        $parts     = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $parts;
        $pluginName = Str::studly($pluginName);

        // まとめたオプション
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
            fileType: 'custom/' . ($fileType === 'plugin' ? 'plugins' : 'core'),
            options: $options,
            subDirs: $subDirs,
            pluginName: $pluginName,
            licenseInfo: $licenseInfo
        );

        return Command::SUCCESS;
    }
}
