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
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;


/**
 * コントローラを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeControllerTrait
{
    use MakeFileTrait;
    use MakeLicenseTrait;

    /**
     * コントローラーコマンドの共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{--api : ' . __('commands.make.options.api') . '}',
            '{--i|invokable : ' . __('commands.make.options.invokable') . '}',
            '{--m|model= : ' . __('commands.make.options.model') . '}',
            '{--p|parent= : ' . __('commands.make.options.parent') . '}',
            '{--r|resource : ' . __('commands.make.options.resource') . '}',
            '{--R|requests : ' . __('commands.make.options.requests') . '}',
            '{--s|singleton : ' . __('commands.make.options.singleton') . '}',
            '{--creatable : ' . __('commands.make.options.creatable') . '}',
            '{--test : ' . __('command.make.options.test') . '}',
            '{--pest : ' . __('command.make.options.pest') . '}',
            '{--phpunit : ' . __('command.make.options.phpunit') . '}',
        ];
    }


    /**
     * コントローラを作成するメイン処理。
     *
     * @param  string  $className  クラス名
     * @param  string  $fileType   ファイルタイプ
     * @param  array   $options    オプション配列
     * @param  array   $subDirs    サブディレクトリ配列
     * @param  string  $pluginName プラグイン名
     * @param  string  $scope      スコープ (front/adminなど)
     * @return bool
     */
    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = '', array $licenseInfo = [])
    {

        $scope = $options['scope'] ?? 'plain'; // スコープの取得（例: admin, front, plain）
        
        // スタブの取得（スコープを考慮）
        $stub = $this->renderStub($options, $scope);
    
    
        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'controller',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: [],
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );
        
        // テスト生成
        if ($options['test'] ?? false || $options['pest'] ?? false || $options['phpunit'] ?? false) {
            $this->createTest($className, $fileType, $subDirs, $pluginName, $options);
        }
        
        return true;
    }

    /**
     * Create a test for the controller
     */
    protected function createTest($className, $fileType, $subDirs, $pluginName = '', $options = [])
    {
        $testName = class_basename($className) . 'Test';
        
        $command = 'make:custom:test';
        if ($fileType === 'plugin') {
            $command = 'make:plugin:test';
        }
        
        $this->call($command, array_filter([
            'name' => $testName,
            'pluginName' => $fileType === 'plugin' ? $pluginName : null,
            '--pest' => $options['pest'] ?? false,
            '--phpunit' => $options['phpunit'] ?? false,
        ]));
    }


    protected function renderStub(array $options = [], string $scope = 'plain'): string
    {
        // コントローラのベースとなるスタブを取得
        $base = file_get_contents(base_path('stubs/custom/controller/controller.base.stub'));

        $scopeHead = '';
        $scopeUse = '';
        $scopeConstruct = '';

        if (in_array($scope, ['admin', 'front'])) {
            $scopeHead = $this->getFragment("{$scope}.head") ?? '';
            $scopeUse = $this->getFragment("{$scope}.use") ?? '';
            $scopeConstruct = $this->getFragment("{$scope}.construct") ?? '';
        }

        $headParts = [];
        $useParts = [];
        $bodyParts = [];


        // オプションに応じてフラグメントを追加
        foreach (['model', 'parent', 'resource', 'requests', 'api', 'singleton', 'creatable', 'invokable'] as $opt) {
            if (!empty($options[$opt])) {
                $headParts[] = $this->getFragment("{$opt}.head") ?? '';
                $useParts[] = $this->getFragment("{$opt}.use") ?? '';
                $bodyParts[] = $this->getFragment("{$opt}.body") ?? '';
            }
        }

        $head = trim($scopeHead . "\n" . implode("\n", $headParts));
        $use = trim($scopeUse . "\n" . implode("\n", $useParts));
        $body = trim(implode("\n", $bodyParts));

        // 置換
        return $this->replacePlaceholders($base, [
            'head'      => $head,
            'use'       => $use,
            'construct' => $scopeConstruct,
            'body'      => $body,
        ]);
    }

    protected function getFragment(string $key): ?string
    {
        $path = base_path("stubs/custom/controller/controller.{$key}.stub");
        return file_exists($path) ? file_get_contents($path) : null;
    }
}
