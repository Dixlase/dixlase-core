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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeCompress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:zip 
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--output-dir= : Output directory for the zip file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress a theme directory into a zip file';

    /**
     * Directories and files to exclude from the zip
     */
    protected array $excludeList = [
        '.git',
        '.github',
        '.idea',
        '.vscode',
        '.DS_Store',
        'node_modules',
        'vendor',
        'composer.lock',
        '__MACOSX',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = Str::studly($this->argument('theme'));
        $themeDir = base_path("themes/{$themeName}");

        if (!File::exists($themeDir)) {
            $this->error("Theme directory not found: {$themeDir}");
            return 1;
        }

        // 出力先ディレクトリ
        $outputDir = $this->option('output-dir') ?: base_path('themes');

        // theme.jsonからバージョンを取得
        $themeJsonPath = $themeDir . '/theme.json';
        $version = 'unknown';
        if (File::exists($themeJsonPath)) {
            $jsonData = json_decode(File::get($themeJsonPath), true);
            if (isset($jsonData['version'])) {
                $version = $jsonData['version'];
            }
        }

        // Zipファイルのパス
        $zipFilePath = rtrim($outputDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . "{$themeName}-v{$version}.zip";

        // 既存zipがあれば削除
        if (File::exists($zipFilePath)) {
            File::delete($zipFilePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            $this->error("Failed to create zip file: {$zipFilePath}");
            return 1;
        }

        $this->info("Creating zip file: {$zipFilePath}");

        // ファイルを再帰的に追加
        $files = File::allFiles($themeDir);
        $addedCount = 0;

        foreach ($files as $file) {
            $relativePath = str_replace($themeDir . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            
            // 除外リストチェック
            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $zip->addFile($file->getRealPath(), "{$themeName}/{$relativePath}");
            $addedCount++;
        }

        $zip->close();

        $this->info("Zip file created successfully!");
        $this->info("Files added: {$addedCount}");
        $this->info("Output: {$zipFilePath}");

        return 0;
    }

    /**
     * Check if a file should be excluded
     */
    protected function shouldExclude(string $path): bool
    {
        foreach ($this->excludeList as $exclude) {
            if (str_contains($path, $exclude)) {
                return true;
            }
        }
        return false;
    }
}
