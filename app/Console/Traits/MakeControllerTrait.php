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


/**
 * コントローラを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeControllerTrait
{
    use MakeFileTrait;
    use MakeLicenseTrait;

    protected $options = [];
    protected $namespace = '';

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
        string $className,
        array $subDirs,
        array $options,
        string $type,
        string $name,
        array $licenseInfo = [],
    ): void {

        $this->options = $options;

        // コントローラのスタブファイルを生成
        $stub = $this->renderStub();

        // `makeFiler` を実行して、コントローラを生成
        $this->makeFiler($className, $subDirs, $options, $type, $name, $stub, 'controller', [], $licenseInfo);
    }




    protected function renderStub()
    {


        $scope = $this->options['scope']; // admin, front, plain
        $type = $this->options['type'] ?? 'default'; // model, api など
        $base = file_get_contents(base_path('stubs/custom/fragments/controller.base.stub'));


        // scope に応じて head / construct / use を読み込み（plainは除外）
        $scopeHead = $scopeConstruct = $scopeUse = '';

        if (in_array($scope, ['admin', 'front'])) {
            $scopeHead = $this->getFragment("{$scope}.head") ?? '';
            $scopeUse = $this->getFragment("{$scope}.use") ?? '';
            $scopeConstruct = $this->getFragment("{$scope}.construct") ?? '';
        }


        // typeによる head/body
        $typeHead = '';
        if (!empty($type) && $type !== 'default') {
            $typeHead = $this->getFragment("controller.{$type}.head") ?? '';
        }
        $body = $this->getFragment("{$type}.body")
            ?? $this->getFragment("default.body")
            ?? '';

        // headを結合（type.head → scope.head の順で上に並ぶ）
        $head = trim($typeHead . "\n" . $scopeHead);
        // 置換
        $base = $this->renderStubWithPlaceholders($base, [
            'head'      => $head,
            'use'       => $scopeUse,
            'construct' => $scopeConstruct,
            'body'      => $body,
            'namespace' => $this->namespace ?? '',
        ]);


        return $this->trimAndIndent($base);
    }



    protected function getFragment(string $key): ?string
    {
        $path = base_path("stubs/custom/fragments/controller.{$key}.stub");
        return file_exists($path) ? file_get_contents($path) : null;
    }

    protected function renderStubWithPlaceholders(string $template, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }
        return $template;
    }



    protected function buildPath(string $basePath, string $className, array $subDirs): string
    {
        $scopedSubDirs = $this->applyScopeToSubDirs($subDirs); // Admin / Front を先頭に付加
        $path = $basePath;

        if (!empty($scopedSubDirs)) {
            $path .= '/' . implode('/', $scopedSubDirs);
        }

        return $path . '/' . $className . '.php';
    }




    protected function applyScopeToSubDirs(array $subDirs): array
    {
        $scope = $this->option('scope') ?? 'plain';

        return match ($scope) {
            'admin' => array_merge(['Admin'], $subDirs),
            'front' => array_merge(['Front'], $subDirs),
            default => $subDirs,
        };
    }



    protected function getClassName(): string
    {
        $path = str_replace('\\', '/', $this->argument('name'));
        $parts = explode('/', $path);
        return Str::studly(array_pop($parts));
    }




    /*

    protected function getControllerNamespace(string $vendorType, string $name, array $subDirs): string
    {
        $nameStudly = Str::studly($name);
        $base = "{$vendorType}\\{$nameStudly}\\App\\Http\\Controllers";
        return $this->buildNamespace($base, $subDirs);
    }



    protected function getPath(string $className, array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/app/Http/Controllers");
        return $this->buildPath($base, $className, $subDirs);
    }






    */
}
