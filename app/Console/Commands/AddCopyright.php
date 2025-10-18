<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddCopyright extends Command
{
    // コマンドの識別名と説明を設定
    protected $signature = 'copyright:update {--dir=app : 変更するファイルがあるディレクトリ(カンマ区切りで複数指定可)}';
    protected $description = 'PHPおよびBladeファイルの著作権表示を挿入または更新する';

    // コマンドの実行ロジック
    public function handle()
    {

        // カンマ区切りで渡されたディレクトリを配列に変換
        $directories = explode(',', $this->option('dir'));

        // 各ディレクトリを処理
        foreach ($directories as $directory) {
            $directory = trim($directory); // 前後の空白を削除

            // ディレクトリが存在するか確認
            if (!File::exists(base_path($directory))) {
                $this->error("ディレクトリ '{$directory}' が見つかりません。");
                exit(1);
            }

            // 対象となるすべてのPHPおよびBladeファイルを取得
            $files = File::allFiles(base_path($directory));

            // ループして各ファイルを処理
            foreach ($files as $file) {

                if ($file->getFilename() === 'license-info.json') continue;

                $dirPath = dirname($file->getPathname());
                $licenseInfo = $this->getLicenseInfo($dirPath);
                // もし null または不正なデータなら、ルートの license-info.json を読み込む
                if (!$licenseInfo) {
                    $this->error("エラー: 有効なライセンス情報を取得できませんでした。");
                    exit(1);
                }

                $copyrightTemplate = $this->getCopyrightTemplate($licenseInfo);

                // 自身のファイルを除外
                if ($file->getPathname() === base_path('app/Console/Commands/AddCopyright.php')) {
                    continue;
                }

                $filename = $file->getFilename();

                // Bladeファイルかどうかをチェック
                if (strpos($filename, '.blade.php') !== false) {
                    $extension = 'blade.php'; // Bladeファイルとして処理
                } else {
                    // 通常の拡張子を取得
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                }

                // 拡張子に基づいて処理を実行
                $this->updateCopyright($file, $extension, $licenseInfo, $copyrightTemplate);
            }
        }


        $this->info('著作権表示が更新されました。');
    }

    // ライセンス情報を取得する
    protected function getLicenseInfo($dirPath)
    {
        $licenseFilePath = $dirPath . '/license-info.json';
        if (!File::exists($licenseFilePath)) {
            $licenseFilePath = base_path('license-info.json');
        }

        // ライセンス情報が存在しない場合は即時終了
        if (!File::exists($licenseFilePath)) {
            $this->error("エラー: ライセンス情報ファイル '{$licenseFilePath}' が見つかりません。");
            exit(1);
        }

        $licenseInfo = json_decode(File::get($licenseFilePath), true);

        if (!$licenseInfo) {
            $this->error("エラー: ライセンス情報が無効です。JSONの構造を確認してください。");
            exit(1);
        }

        return $licenseInfo;
    }


    // ライセンステンプレートを読み込む
    protected function getCopyrightTemplate($licenseInfo)
    {
        $templateFilePath = base_path('license-templates/' . $licenseInfo['template']);

        if (!File::exists($templateFilePath)) {
            $this->error("エラー: テンプレートファイル '{$templateFilePath}' が見つかりません。");
            exit(1);
        }

        return File::get($templateFilePath);
    }


    // ファイルに著作権表示を挿入または更新する処理
    protected function updateCopyright($file, $extension, $licenseInfo, $copyrightTemplate)
    {
        $content = File::get($file->getPathname());
        $year = date('Y');

        // 新しい著作権表示
        $newCopyright = str_replace(
            ['{year}', '{author}', '{software}', '{website}', '{license}'],
            [$year, $licenseInfo['author'], $licenseInfo['software'], $licenseInfo['website'], $licenseInfo['license']],
            $copyrightTemplate
        );

        // Bladeファイルの場合の処理
        if ($extension == 'blade.php') {
            // Bladeコメント形式に変換
            $newCopyright = "\n{{--\n" . $newCopyright . "\n--}}\n";
        } else {
            // PHPコメント形式に変換（各行の前に `*` を追加）
            $newCopyrightLines = explode("\n", $newCopyright);
            $formattedCopyright = "/**\n";

            foreach ($newCopyrightLines as $line) {
                // 空白行は `*` のみ、それ以外は `* ` を付ける
                $formattedCopyright .= (trim($line) === '') ? " *\n" : " * " . rtrim($line) . "\n";
            }

            $formattedCopyright .= " */";
            $newCopyright = $formattedCopyright;
        }

        // Bladeファイルの正規表現（既存のライセンスを置換）
        $bladeRegex = '/\{\{\-\-.*?Copyright.*?\-\-\}\}/s';
        // PHPファイルの正規表現（既存のライセンスを置換）
        $phpRegex = '/\/\*\*.*?Copyright.*?\*\//s';

        // すでに著作権表示があるかどうか確認
        if ($extension == 'blade.php' && preg_match($bladeRegex, $content)) {
            // 既存の著作権表示を新しいものに置き換える（Bladeファイル）
            $content = preg_replace($bladeRegex, $newCopyright, $content);
        } elseif (preg_match($phpRegex, $content)) {
            // 既存の著作権表示を新しいものに置き換える（PHPファイル）
            $content = preg_replace($phpRegex, $newCopyright, $content);
        } else {
            // 余分な改行を削除
            $content = ltrim($content, "\n");

            // 新しい著作権表示を挿入
            switch ($extension) {
                case 'blade.php':  // Bladeファイル
                    if (preg_match('/<!DOCTYPE html>/', $content)) {
                        $content = preg_replace('/(<!DOCTYPE html>)/', $newCopyright . "\n$1", $content, 1);
                    } else {
                        $content = $newCopyright . "\n\n" . $content;
                    }
                    break;
                case 'php':        // PHPファイル
                case 'stub':       // スタブファイル
                    if (strpos($content, '<?php') === false) {
                        $content = "<?php\n" . $newCopyright . "\n" . $content;
                    } else {
                        $content = preg_replace('/(<\?php\s*)/', '$1' . $newCopyright . "\n", $content, 1);
                    }
                    break;
                case 'js':         // JavaScriptファイル
                case 'scss':       // SCSSファイル
                    $content = $newCopyright . "\n" . $content;
                    break;
                default:
                    // 他のファイル形式に対する処理
                    break;
            }
        }

        // 最後の余分な空白行を削除
        $content = rtrim($content) . "\n";

        // ファイルに変更を保存
        File::put($file->getPathname(), $content);
    }
}
