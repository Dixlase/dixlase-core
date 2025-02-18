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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * 言語ファイルを作成する共通ロジック
 */
trait MakeLanguageTrait
{
    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * 言語ファイルを作成する
     *
     * @param string $langCode 言語コード (例: "en", "ja")
     * @param string $fileName ファイル名 (例: "messages")
     * @param array $options オプション
     */
    protected function makeLanguageFile(string $langCode, string $fileName, array $options): void
    {
        // 言語ファイルの拡張子を `.php` に統一
        if (!str_ends_with($fileName, '.php')) {
            $fileName .= '.php';
        }

        // 言語ファイルの保存パスを取得
        $filePath = $this->getDirectory([$langCode]) . '/' . $fileName;

        try {
            $this->fileGenerator->prepareFilePath($filePath, "Language file [{$fileName}] already exists. Use --force to overwrite.");
        } catch (\RuntimeException $e) {
            if (!($options['force'] ?? false)) {
                $this->error($e->getMessage());
                return;
            }
            File::delete($filePath);
        }

        // スタブファイルを取得
        $stubFile = $this->resolveStubFile($langCode);
        $stubContent = $this->fileGenerator->getStubContent($stubFile);

        // プレースホルダー置換
        $placeholders = [
            '{{ license }}' => $this->fileGenerator->getLicenseContent(),
            '{{ langCode }}' => $langCode,
            '{{ fileName }}' => Str::studly($fileName),
        ];

        $finalContent = $this->fileGenerator->replacePlaceholders($stubContent, $placeholders);
        $this->fileGenerator->generateFile($filePath, $finalContent);

        $this->info("Language file created: {$filePath}");
    }

    /**
     * 言語ファイルのスタブを選択
     */
    protected function resolveStubFile(string $langCode): string
    {
        return "messages.{$langCode}.stub";
    }

    /**
     * 言語ファイルの保存先ディレクトリを取得（抽象）
     */
    abstract protected function getDirectory(array $subDirs): string;

    /**
     * 言語ファイルにはネームスペースは不要
     */
    protected function getNamespace(array $subDirs): string
    {
        return '';
    }
}
