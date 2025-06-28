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
            '{--invokable} ' . __('commands.make.options.invokable'),
            '{--model=} ' . __('commands.make.options.model'),
            '{--parent=} ' . __('commands.make.options.parent'),
            '{--resource} ' . __('commands.make.options.resource'),
            '{--requests} ' . __('commands.make.options.requests'),
            '{--api} ' . __('commands.make.options.api'),
            '{--singleton} ' . __('commands.make.options.singleton'),
            '{--creatable} ' . __('commands.make.options.creatable'),
        ];
    }

    /**
     * コントローラを作成するメイン処理。
     * MakeFileTrait::makeFiler() を呼ぶ前後で、
     * コントローラ固有の stub選択 / 追加置換を加える。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  array   $options
     * @return void
     */

    protected function makeFile(
        string $className, //クラス名
        string $fileType, //プラグイン用かカスタムファイル用か
        array $options, //オプション
        array $subDirs, //サブディレクトリ
        string $pluginName = '', //プラグイン名
        array $licenseInfo = [], //ライセンス情報
    ): void {
        $this->options = $options;

        // コントローラのスタブファイルを生成
        $stub = $this->renderStub($options['scope'] ?? 'plain', $options);
        
        // `makeFiler` を実行して、コントローラを生成
        $this->makeFiler($className, $fileType, 'controllers', $options, $subDirs, $stub, $pluginName, [], $licenseInfo);
    }


    protected function renderStub(string $scope = 'plain', array $options = []): string
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
