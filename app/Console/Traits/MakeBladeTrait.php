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

use App\Services\FileGenerator;
use Illuminate\Support\Facades\File;

/**
 * Bladeファイルを作成するためのTrait。
 * -> いわゆる「クラス + namespace」が存在しないため、MakeFileTraitは使わず、
 *    シンプルに「フォルダ+ファイル」を生成するだけに特化する。
 */
trait MakeBladeTrait
{
    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Bladeファイルを作成するメイン処理
     *
     * @param  string  $viewName   例: "admin/dashboard", "home/index" など
     * @param  bool    $force
     * @return void
     */
    protected function makeBlade(string $viewName, array $options): void
    {
        if (! str_ends_with($viewName, '.blade.php')) {
            $viewName .= '.blade.php';
        }

        $filePath = $this->getBladeBasePath() . '/' . $viewName;

        try {
            $this->fileGenerator->prepareFilePath($filePath, "Blade file [{$viewName}] already exists. Use --force to overwrite.");
        } catch (\RuntimeException $e) {
            if (!($options['force'] ?? false)) {
                $this->error($e->getMessage());
                return;
            }
            File::delete($filePath);
        }

        $stubFile = $this->resolveStubFile($options);
        $stubContent = $this->fileGenerator->getStubContent($stubFile);

        // Blade用のライセンスコメントを追加
        $placeholders = [
            '{{ license }}' => $this->fileGenerator->getLicenseForBlade(),
            '{{ filename }}' => str_replace('.blade.php', '', $viewName),
        ];

        $finalContent = $this->fileGenerator->replacePlaceholders($stubContent, $placeholders);
        $this->fileGenerator->generateFile($filePath, $finalContent);

        $this->info("Blade file created: {$filePath}");
    }

    /**
     * フロント用・管理画面用のBladeテンプレートを選択
     */
    protected function resolveStubFile(array $options): string
    {
        return ($options['type'] ?? 'front') === 'admin' ? 'blade-admin.stub' : 'blade-front.stub';
    }

    /**
     * Bladeファイルの保存先を定義（抽象）
     */
    abstract protected function getBladeBasePath(): string;
}
