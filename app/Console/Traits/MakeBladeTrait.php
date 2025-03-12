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

/**
 * Bladeファイルを作成するためのTrait。
 * -> いわゆる「クラス + namespace」が存在しないため、MakeFileTraitは使わず、
 *    シンプルに「フォルダ+ファイル」を生成するだけに特化する。
 */
trait MakeBladeTrait
{
    // Bladeファイルを作成
    protected function makeFile(string $viewName, array $subDirs, array $options): void
    {
        // 1) 余分な拡張子を取り除く
        if (str_ends_with($viewName, '.php')) {
            $viewName = substr($viewName, 0, -4);
        }
        if (str_ends_with($viewName, '.blade')) {
            // 例: "upload.blade" -> "upload"
            $viewName = substr($viewName, 0, -6);
        }

        // 2) サブディレクトリとファイル名を切り分ける
        //    例: "admin/media/upload" -> ["admin","media","upload"]
        $parts = explode('/', $viewName);
        $fileName = array_pop($parts);       // "upload"
        $subDirs = $parts;                  // ["admin","media"]

        // 3) ファイル名をケバブケース化
        //    ex: "upload.blade" はほぼ変化なし ("upload.blade")
        //        "SomePage.blade" → "some-page.blade"
        $fileName = Str::kebab($fileName) . '.blade.php';

        // Blade用ライセンスコメントなど
        $extraPlaceholders = [
            '{{ license }}'  => $this->fileGenerator->getLicenseForBlade(),
            '{{ filename }}' => $viewName,  // 例: "admin/media/upload"
        ];

        // 5) Blade専用ロジックでファイル作成
        $this->makeFilerBlade($fileName, $subDirs, $options, $this->resolveStubFile($options), $extraPlaceholders);
    }

    /**
     * Blade専用に実際のファイルを作成
     */
    /**
     * Blade専用に実際のファイルを作成
     */
    protected function makeFilerBlade(
        string $fileName,
        array $subDirs,
        array $options,
        string $stubFile,
        array $extraPlaceholders
    ): void {
        // (A) サブディレクトリを /resources/views/admin/media のように組み立て
        $targetDirectory = $this->getDirectory($subDirs);
        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        // (B) 最終的なファイルパス
        $filePath = rtrim($targetDirectory, '/') . '/' . $fileName;

        // (C) --force オプション
        $force = $options['force'] ?? false;
        if (! $force) {
            $this->fileGenerator->prepareFilePath($filePath, "[{$fileName}] already exists.");
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // (D) スタブ読み込み
        $stubContent = $this->loadStubFile($stubFile);

        // (E) ライセンスなどを埋め込み
        $finalContent = $this->fileGenerator->embedLicenseBlade($stubContent, $extraPlaceholders);

        // (F) 実ファイル作成
        $this->fileGenerator->generateFile($filePath, $finalContent);

        $this->info("Blade file [{$filePath}] created successfully.");
    }

    /**
     * Blade用: サブディレクトリを結合したディレクトリパスを返す
     */
    protected function getDirectory(array $subDirs): string
    {
        $basePath = resource_path('views');
        if (! empty($subDirs)) {
            $basePath .= '/' . implode('/', $subDirs);
        }
        return $basePath;
    }

    /**
     * フロント or 管理画面 用のスタブを切り替え
     */
    protected function resolveStubFile(array $options): string
    {
        return ($options['type'] ?? 'front') === 'admin'
            ? 'blade-admin.stub'
            : 'blade-front.stub';
    }

    /**
     * スタブファイルを読み込み (親 trait のメソッドを独自に定義・上書き)
     */
    protected function loadStubFile(string $stubFile): string
    {
        $customStubPaths = [base_path('stubs/custom')];
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFile}");

        return $this->fileGenerator->getStubContent($stubFile, $defaultStubPath, $customStubPaths);
    }
}
