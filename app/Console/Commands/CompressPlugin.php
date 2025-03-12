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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class CompressPlugin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:zip {pluginName} {--output-dir= : Zipファイルの出力先ディレクトリ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定したプラグインフォルダをzipファイルにまとめます';

    // 除外したいディレクトリ名やファイル名、パターンを配列で定義
    // （大文字小文字問わず判定したい場合は strtolower() でそろえるなど工夫も可能）
    protected array $excludeList = [
        '.git',
        '.github',
        '.idea',
        '.vscode',
        '.DS_Store',
        'node_modules',
        'vendor',    // ※プラグインで必要なvendorなら除外しない方がいい場合も
        'composer.lock', // composer.lock不要と判断するなら
        '__MACOSX',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('pluginName');
        $pluginDir  = base_path("plugins/{$pluginName}");

        if (!File::exists($pluginDir)) {
            $this->error("プラグインフォルダが見つかりません: {$pluginDir}");
            return 1;
        }

        // 出力先ディレクトリ（オプション未指定なら plugins/ へ）
        $outputDir = $this->option('output-dir');
        if (!$outputDir) {
            $outputDir = base_path('plugins');
        }


        // composer.json から version を取得 (ある場合のみ)
        $composerPath = $pluginDir . '/composer.json';
        $version = 'unknown';
        if (File::exists($composerPath)) {
            $jsonData = json_decode(File::get($composerPath), true);
            if (isset($jsonData['version'])) {
                $version = $jsonData['version'];
            }
        }

        // Zipファイルの出力先パス
        // 例) plugins/MyPlugin-v1.0.0.zip
        $zipFilePath = rtrim($outputDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . "{$pluginName}-v{$version}.zip";

        // 既存zipがあれば削除
        if (File::exists($zipFilePath)) {
            File::delete($zipFilePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            $this->error("zipファイルを作成できません: {$zipFilePath}");
            return 1;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                // zip内の相対パス (例: MyPlugin/以下)
                $relativePath = $pluginName . '/' . substr($filePath, strlen($pluginDir) + 1);

                // 除外対象であればスキップ
                if ($this->shouldExclude($relativePath)) {
                    continue;
                }

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        $this->info("プラグインをzip化しました: {$zipFilePath}");

        return 0;
    }

    /**
     * 除外リストに該当するかどうか判定する
     */
    protected function shouldExclude(string $path): bool
    {
        // パスにexcludeListの要素のいずれかが含まれている場合は除外
        // 例: node_modules などのディレクトリを含むパスには "node_modules" が含まれる
        foreach ($this->excludeList as $exclude) {
            // 単純に "path の中に $exclude が含まれていれば" 除外 という判定
            // 大文字小文字無視したい場合は stripos() にする
            if (strpos($path, $exclude) !== false) {
                return true;
            }
        }
        return false;
    }
}
