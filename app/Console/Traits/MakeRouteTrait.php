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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Console\Traits\MakePluginCommandTrait;

/**
 * ルートファイル作成用トレイト。
 * -> MakeFileTrait を use し、ルートファイル特有のロジックを追加。
 */
trait MakeRouteTrait
{
    use MakeFileTrait, MakeCustomCommandTrait, MakePluginCommandTrait;
    
    /**
     * ルートファイル固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [];
    }

    /**
     * ルートファイル用の初期化処理
     *
     * @param string $classPath
     * @return array|false
     */
    protected function initializeRouteCommand(string $classPath)
    {
        

        // プラグインルートの場合はファイルタイプ選択をスキップ
        $isPlugin = $this->getName() === 'make:plugin:route';
        
        if ($isPlugin) {
            $fileType = 'plugin';
            $pluginName = $this->choosePlugin();
            if (!$pluginName) {
                $this->error(__('command.plugin.not_found'));
                return false;
            }
        } else {
            // ファイルタイプの選択（カスタム or プラグイン）
            [$fileType, $pluginName] = $this->chooseFileType();
            if (!$fileType) {
                return false;
            }
        }

        // ルートタイプの選択肢を定義
        $routeTypes = [
            1 => [
                'key' => 'web',
                'name' => __('command.make.route_types.web'),
                'description' => __('command.make.route_types.web_description')
            ],
            2 => [
                'key' => 'admin',
                'name' => __('command.make.route_types.admin'),
                'description' => __('command.make.route_types.admin_description')
            ],
            3 => [
                'key' => 'api',
                'name' => __('command.make.route_types.api'),
                'description' => __('command.make.route_types.api_description')
            ]
        ];

        // 選択肢を表示
        $this->info(__('command.make.select_route_type'));
        foreach ($routeTypes as $number => $type) {
            $this->line(sprintf(
                "  [%d] %s - %s",
                $number,
                str_pad($type['name'], 10, ' ', STR_PAD_RIGHT),
                $type['description']
            ));
        }


        // 選択を取得
        $selected = (int)$this->ask(__('command.make.enter_route_type'), 1);
        $routeType = isset($routeTypes[$selected]['key']) ? $routeTypes[$selected]['key'] : 'web';

        // パス情報の分解（スコープは使用しない）
        $className = $this->parseRoutePath($classPath);

        // ライセンス情報を取得
        $licenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);

        // スタブファイルの決定
        $stub = "routes.{$routeType}";


        return [
            'fileType' => $fileType,
            'category' => 'route',
            'pluginName' => $pluginName,
            'routeType' => $routeType,
            'className' => $className,
            'subDirs' => [], // サブディレクトリは使用しない
            'stub' => $stub,
            'licenseInfo' => $licenseInfo,
        ];
    }
    

    /**
     * ルートファイルを作成するメイン処理。
     *
     * @param  string  $className     ルートファイル名 (e.g. "web")
     * @param  string  $fileType      ファイルタイプ (custom_core, custom_plugin, plugin)
     * @param  array   $options       コマンドオプション
     * @param  array   $subDirs       サブディレクトリ (["admin"] など)
     * @param  string  $pluginName    プラグイン名
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '', $licenseInfo = [])
    {
        // スタブファイルのパスを取得（ルートタイプを使用）
        $stubPath = $this->getStubPath($options['routeType'] ?? 'web', $options);
        if (!$stubPath) {
            return false;
        }

        // スタブファイルの内容を取得
        $stub = file_exists($stubPath) ? file_get_contents($stubPath) : '';
        
        // ファイル生成
        return $this->makeFiler(
            $className,
            $fileType,
            'routes',
            $options,
            $subDirs,
            $stub,
            $pluginName,
            [], // 追加のプレースホルダーは不要
            $licenseInfo
        );
    }

    

    /**
     * ルートパスをパース
     * 
     * @param string $path
     * @return string
     */
    protected function parseRoutePath(string $path): string
    {
        // パスからファイル名を取得（拡張子を除く）
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        $path = pathinfo($path, PATHINFO_FILENAME);
        
        // スネークケースに変換
        return Str::snake($path);
    }

    /**
     * スタブファイルのパスを取得
     */
    protected function getStubPath($fileType, $options)
    {
        $stubPath = '';
        $routeType = $options['routeType'] ?? 'web';

        // カスタムスタブディレクトリを確認
        $customStubDir = config('command.custom_stub_directory');
        if ($customStubDir && is_dir($customStubDir)) {
            $stubPath = $customStubDir . '/routes.' . $routeType . '.stub';
            if (file_exists($stubPath)) {
                return $stubPath;
            }
        }

        // デフォルトのスタブファイルを使用
        $stubPath = config('command.default_stub_directory') . '/routes.' . $routeType . '.stub';
        if (!file_exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return false;
        }

        return $stubPath;
    }

    /**
     * ルートファイルのオプションを処理
     *
     * @param  string  $className
     * @param  array   $options
     * @param  string  $fileType
     * @param  array   $subDirs
     * @param  string  $pluginName
     * @return bool
     */
    protected function handleOptions($className, $options, $fileType, $subDirs = [], $pluginName = '')
    {
        // ルートファイルの場合は特に追加のオプション処理は不要
        return true;
    }


    /**
     * ルートファイルのスタブを取得
     *
     * @param  array  $options
     * @return string
     */
    protected function getStub($options = [])
    {
        $type = 'web';
        if ($options['api'] ?? false) {
            $type = 'api';
        } elseif ($options['admin'] ?? false) {
            $type = 'admin';
        }

        $stubPath = config('command.custom_stub_directory') . "/routes.{$type}.stub";
        
        if (!file_exists($stubPath)) {
            $stubPath = config('command.custom_stub_directory') . '/routes.web.stub';
        }

        return $stubPath;
    }
}
