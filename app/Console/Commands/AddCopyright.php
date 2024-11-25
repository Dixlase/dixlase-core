<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
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

    // 著作権表示テンプレート
    protected $copyrightTemplate = <<<EOT
/**
 * This file is part of {software}.
 *
 * Copyright (C) {year} {company}
 * Website: {website}
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
EOT;

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
                continue; // ディレクトリが存在しない場合はスキップ
            }

            // 対象となるすべてのPHPおよびBladeファイルを取得
            $files = File::allFiles(base_path($directory));

            // ループして各ファイルを処理
            foreach ($files as $file) {
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
                $this->updateCopyright($file, $extension);
            }
        }


        $this->info('著作権表示が更新されました。');
    }

    // ファイルに著作権表示を挿入または更新する処理
    protected function updateCopyright($file, $extension)
    {
        $content = File::get($file->getPathname());
        $year = date('Y');
        $company = 'exc-D inc.';
        $software = 'Your Software Name';
        $website = 'https://exc-d.com';

        // 新しい著作権表示
        $newCopyright = str_replace(
            ['{year}', '{company}', '{software}', '{website}'],
            [$year, $company, $software, $website],
            $this->copyrightTemplate
        );

        //Bladeファイルの場合の処理
        if ($extension == 'blade.php') {
            //Bladeのコメント形式に変換
            $newCopyright = str_replace(
                ['/**', ' */'],
                ['{{--', '--}}'],
                $newCopyright
            );
            // 各行の先頭の*を削除
            $newCopyright = preg_replace('/ \* ?/', '', $newCopyright);

            $newCopyright = preg_replace(
                ['/ \*\ /', '/ \*/'],
                ['', ''],
                $newCopyright
            );
        }

        // Bladeファイルの場合の正規表現修正
        $bladeRegex = '/\{\{\-\-.*?Copyright.*?\-\-\}\}/s';
        // PHPファイルの場合の正規表現修正
        $phpRegex = '/\/\*\*.*?Copyright.*?\*\//s';

        // すでに著作権表示があるかどうか確認
        if ($extension == 'blade.php' && preg_match($bladeRegex, $content)) {
            // 既存の著作権表示を新しいものに置き換える（Bladeファイル）
            $content = preg_replace($bladeRegex, $newCopyright, $content);
        } elseif (preg_match($phpRegex, $content)) {
            // 既存の著作権表示を新しいものに置き換える（PHPファイル）
            $content = preg_replace($phpRegex, $newCopyright, $content);
        } else {
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

        // ファイルに変更を保存
        File::put($file->getPathname(), $content);
    }
}
