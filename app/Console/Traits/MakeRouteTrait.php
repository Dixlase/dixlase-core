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

namespace App\Console\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Console\Traits\MakePluginCommandTrait;

/**
 * ルートファイル作成用トレイト。
 * MakeModelTrait と同じパターンに従う
 */
trait MakeRouteTrait
{
    use MakeFileTrait, MakeLicenseTrait, MakeCustomCommandTrait, MakePluginCommandTrait;
    
    /**
     * ルートファイル固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{routeType? : The route type (e.g. web, admin, api)}',
            '{--placeholders= : JSON encoded placeholders for stub replacement}',
            '{--license-info= : JSON encoded license information}',
        ];
    }

    
    /**
     * ルートファイルを作成するメイン処理。
     *
     * @param  string  $className     ルートファイル名 (e.g. "web")
     * @param  string  $fileType      ファイルタイプ (core, custom_plugin, plugin)
     * @param  array   $options       コマンドオプション
     * @param  array   $subDirs       サブディレクトリ (["admin"] など)
     * @param  string  $pluginName    プラグイン名
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '', $licenseInfo = [])
    {

        // ルートタイプの取得と検証
        $routeType = null;
        if (isset($options['routeType']) && $options['routeType']) {
            $routeType = strtolower($options['routeType']);
        }

        // ルートタイプが指定されていない場合は選択を求める
        if (!$routeType) {
            $routeType = $this->chooseRouteType();
        }

        // 有効なルートタイプか検証
        $validRouteTypes = ['web', 'admin', 'api'];
        if (!in_array($routeType, $validRouteTypes, true)) {
            $this->error(sprintf(
                '無効なルートタイプです: %s (有効な値: %s)',
                $routeType,
                implode(', ', $validRouteTypes)
            ));
            return false;
        }

        $options['routeType'] = $routeType;        

        // スタブファイルの内容を取得
        $stub = $this->renderStub($options);
        if ($stub === false) {
            return false;
        }
        
        // プレースホルダーの取得（JSON形式で渡された場合はデコード）
        $additionalPlaceholders = [];
        if (isset($options['placeholders']) && !empty($options['placeholders'])) {
            $decoded = json_decode($options['placeholders'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $additionalPlaceholders = $decoded;
            }
        }
        
        // ライセンスキーからライセンス情報を取得
        $finalLicenseInfo = $licenseInfo;
        
        // プラグイン情報を取得
        $pluginInfo = [];
        if (!empty($pluginName)) {
            $licenseInfoFile = base_path("plugins/{$pluginName}/license-info.json");
            if (File::exists($licenseInfoFile)) {
                $pluginInfo = json_decode(File::get($licenseInfoFile), true) ?? [];
            }
        }
        
        // --license-infoオプションが指定されている場合はそれを使用、なければlicense-info.jsonから取得
        if (isset($options['license-info']) && !empty($options['license-info'])) {
            $licenseKey = $options['license-info'];
        } elseif (!empty($pluginInfo['license'])) {
            $licenseKey = $pluginInfo['license'];
        } else {
            $licenseKey = null;
        }
        
        if ($licenseKey) {
            $finalLicenseInfo = $this->getLicenseInfoFromKey($licenseKey, $pluginInfo);
        }
        
        // ファイル生成
        return $this->makeFiler(
            $className,
            $fileType,
            'route',
            $options,
            $subDirs,
            $stub,
            $pluginName,
            $additionalPlaceholders, // プレースホルダーを渡す
            $finalLicenseInfo
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
     * ルートタイプに基づいて適切なスタブファイルをレンダリングする
     * 
     * @param array $options オプション
     *        string $options['routeType'] ルートタイプ (web, api, admin)
     *        bool $options['api'] APIルートの場合はtrue
     *        bool $options['admin'] 管理画面ルートの場合はtrue
     *        bool $options['isPlugin'] プラグイン用の場合はtrue
     * @return string|false スタブファイルの内容、または失敗時はfalse
     */
    protected function renderStub(array $options = []): string|false
    {
        // ルートタイプを決定（後方互換性のため古いオプションもサポート）
        $routeType = $options['routeType'] ?? 'web';
        $isPlugin = $options['isPlugin'] ?? false;
        
        // カスタムスタブディレクトリを確認
        $customStubDir = config('command.custom_stub_directory');
        if ($customStubDir && is_dir($customStubDir)) {
            // プラグイン用のスタブファイルを優先
            if ($isPlugin) {
                $stubPath = $customStubDir . '/routes.plugin.' . $routeType . '.stub';
                if (file_exists($stubPath)) {
                    return File::get($stubPath);
                }
            }
            
            // 通常のスタブファイル
            $stubPath = $customStubDir . '/routes.' . $routeType . '.stub';
            if (file_exists($stubPath)) {
                return File::get($stubPath);
            }
        }
        
        return false;
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

    protected function chooseRouteType(){
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
        
        $selected = (int)$this->ask(__('command.make.enter_route_type'), 1);
        $routeType = isset($routeTypes[$selected]['key']) ? $routeTypes[$selected]['key'] : 'web';
        
        return $routeType;
    }
}
