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

/**
 * ファクトリー作成用のトレイト
 */
trait MakeFactoryTrait
{
    use MakeFileTrait;

    /**
     * ファクトリー固有のオプション定義を取得
     * 
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '--model' => 'The name of the model',
        ];
    }

    /**
     * ファクトリーファイルを作成する
     *
     * @param string $className
     * @param string $fileType
     * @param array $options
     * @param array $subDirs
     * @param string $pluginName
     * @return bool
     */
    protected function makeFile(
        string $className,
        string $fileType,
        array $options,
        array $subDirs,
        string $pluginName
    ): bool {
        $className = Str::studly($className);

        // Get the stub content
        $stub = $this->renderStub($options);

        // Prepare placeholders
        $placeholders = [
            'class' => $className,
            'model' => $this->getModelName($className, $options['model'] ?? null),
        ];

        // ファイル生成
        $this->makeFiler(
            className: $className,
            fileType: $fileType,
            fileCategory: 'factory',
            options: $options,
            subDirs: $subDirs,
            stub: $stub,
            pluginName: $pluginName,
            placeholders: $placeholders,
            licenseInfo: $this->getFileTypeLicenseInfo($fileType, $pluginName)
        );

        return true;
    }

    /**
     * モデル名を取得する
     */
    protected function getModelName(string $className, ?string $modelOption): string
    {
        if ($modelOption) {
            return '\\' . ltrim($modelOption, '\\');
        }

        // Remove Factory suffix and convert to model name
        $modelName = Str::replaceLast('Factory', '', $className);
        return '\\App\\Models\\' . $modelName;
    }

    /**
     * スタブをレンダリングする
     *
     * @param array $options
     * @return string
     */
    protected function renderStub(array $options): string
    {
        $stubPath = config('command.custom_stub_directory') . '/factory.stub';
            
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return '';
        }

        return File::get($stubPath);
    }
}
