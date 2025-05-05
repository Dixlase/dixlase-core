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
use App\Console\Traits\MakeLicenseTrait;

use function Ramsey\Uuid\v1;

/**
 * どんな「ファイル作成」コマンドにも共通する基礎ロジックをまとめる Trait
 */
trait MakeFileTrait
{

    use MakeLicenseTrait;

    protected $namespace = '';
    protected $path = '';

    /**
     * 実際にファイルを作成するメイン処理。
     *
     * @param  string  $className   作成するクラス名 (e.g. "MyClass")
     * @param  array   $subDirs     サブディレクトリ ["Admin", "Nested"]
     * @param  array   $options     オプション { force: bool, ... }
     * @param  string  $stubFile    選択されたスタブファイル名
     * @return void
     */



    protected function makeFiler(
        string $className,
        array $subDirs,
        array $options,
        string $type,
        string $name,
        string $stub,
        string $category,
        array $placeholders = [],
        array $licenseInfo = [],
    ): void {


        // 1. 名前空間とパスの生成
        [$this->namespace, $this->path] = $this->getBaseNamespaceAndPath($type, $name, $category, $subDirs);

        //ライセンスの整形
        //$this->info(print_r($licenseInfo, true));




        $license = $this->replacePlaceholders($licenseInfo['template'], $licenseInfo['info']);


        if ($category == 'Blade') { //BladeファイルならBlade用の整形
        } else { //PHPならPHP用の整形
            $license = $this->embedLicenseForPhp($license);
        }


        // 2. プレースホルダの生成
        $defaultPlaceholders = [
            'namespacePath'     => $this->namespace,
            'className'         => $className,
            'rootNamespace'     => app()->getNamespace(),
            'license'           => $license,
        ];

        // 追加の置換をマージ
        $finalPlaceholders = array_merge($defaultPlaceholders, $placeholders);

        //$this->info(print_r($finalPlaceholders, true));

        // 3. ファイル内容生成
        $content = $this->fileGenerator->getStubContent($stub, $finalPlaceholders);

        // 4. パスとファイル名の生成
        $fullPath = $this->path . '/' . $className . '.php';

        // 5. 上書き確認
        if (File::exists($fullPath) && empty($options['force'])) {
            $this->warn("File already exists: {$fullPath}");
            return;
        }

        // 6. ディレクトリ作成
        if (!File::isDirectory($this->path)) {
            File::makeDirectory($this->path, 0755, true);
        }

        // 7. ファイル生成
        File::put($fullPath, $content);
        $this->info("Controller created: {$fullPath}");
    }

    protected function getBaseNamespaceAndPath(string $type, string $name, string $category, array $subDirs): array
    {
        $nameStudly = \Illuminate\Support\Str::studly($name);
        $categoryStudly = \Illuminate\Support\Str::studly($category);

        $namespaceSubDir = implode('\\', $subDirs);
        $pathSubDir = implode('/', $subDirs);

        $namespace = "{$type}\\{$nameStudly}\\App\\Http\\{$categoryStudly}" . ($namespaceSubDir ? "\\{$namespaceSubDir}" : '');
        $path = base_path(strtolower($type) . "/{$nameStudly}/app/Http/{$categoryStudly}" . ($pathSubDir ? "/{$pathSubDir}" : ''));

        return [$namespace, $path];
    }

    protected function makeDirectory(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * プレースホルダを置換
     */
    public function replacePlaceholders(string $stub, array $placeholders): string
    {
        foreach ($placeholders as $search => $replace) {
            $stub = str_replace('{{ ' . $search . ' }}', $replace, $stub);
        }
        return $stub;
    }



    /**
     * ファイルの種類ごとに適切な命名規則を適用
     */

    /*
    private function determineFileName(string $className, string $fileType): string
    {
        $timestamp = date('Y_m_d_His');

        // 設定から命名規則を取得（デフォルトは StudlyCase）
        $namingConvention = config("custom.file_types.{$fileType}.naming_convention", 'studly_case');

        return match ($namingConvention) {
            'snake_case' => Str::snake($className),
            'snake_case_with_timestamp' => "{$timestamp}_" . Str::snake($className),
            'kebab_case' => Str::kebab($className),
            default => Str::studly($className), // デフォルトはキャメルケース
        };
    }


    protected function getRootNamespace(): string
    {
        return app()->getNamespace();
    }
    */
}
