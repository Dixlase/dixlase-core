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

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReplaceComments extends Command
{
    /**
     * コマンドのシグネチャと説明
     *
     * @var string
     */
    protected $signature = 'replace:comments {locale=en}';

    protected $description = 'Replace Japanese comments with English comments based on translation files';

    /**
     * コマンドを実行するメインロジック
     */
    public function handle()
    {
        // ロケールの取得 (デフォルトは "en")
        $locale = $this->argument('locale');
        $translations = $this->loadTranslations($locale);

        if (empty($translations)) {
            $this->error("Translation file for locale '{$locale}' not found.");
            return;
        }

        // ソースコードのディレクトリ
        $directory = base_path('app/Http/Controllers');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if ($file->isFile() && preg_match('/\.php$/', $file->getFilename())) {
                $this->replaceComments($file->getPathname(), $translations);
                $this->info("Updated: " . $file->getPathname());
            }
        }

        $this->info('Comment replacement completed.');
    }

    /**
     * 翻訳ファイルを読み込む
     *
     * @param string $locale
     * @return array
     */
    protected function loadTranslations($locale)
    {
        $filePath = resource_path("lang/{$locale}/comments.php");
        if (file_exists($filePath)) {
            return include $filePath;
        }
        return [];
    }

    /**
     * コメントを置換する処理
     *
     * @param string $filePath
     * @param array $translations
     */
    protected function replaceComments($filePath, $translations)
    {
        $content = file_get_contents($filePath);

        foreach ($translations as $jpComment => $enComment) {
            // 日本語コメントを英語コメントに置換
            $pattern = "/\/\/\s*" . preg_quote($jpComment, '/') . "/";
            $replacement = "// $enComment";
            $content = preg_replace($pattern, $replacement, $content);
        }

        file_put_contents($filePath, $content);
    }
}
