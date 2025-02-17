<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

trait MakeRouteTrait
{
    protected FileGenerator $fileGenerator;

    /**
     * ルートファイルを作成
     */
    protected function makeRouteFile(string $name, string $directory, string $namespace)
    {
        $fileName = Str::studly($name) . '.php';
        $filePath = "{$directory}/{$fileName}";

        // 既存ファイルの確認
        if (File::exists($filePath)) {
            $this->error("The route file '{$fileName}' already exists.");
            return;
        }

        // ライセンス情報を取得
        $licenseText = $this->fileGenerator->getLicenseContent();

        // プレースホルダ
        $placeholders = [
            '{{ license }}'  => $licenseText,
            '{{ namespace }}' => $namespace,
            '{{ fileName }}'  => $fileName,
        ];

        // スタブファイルの取得と置換
        $stubFile = $this->fileGenerator->getStubContent('routes.stub', null, [base_path('stubs/custom')]);
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);

        // ファイルを生成
        $this->fileGenerator->generateFile($filePath, $fileContent);

        $this->info("Route file '{$filePath}' has been created successfully.");
    }
}
