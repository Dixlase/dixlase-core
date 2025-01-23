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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeBlade extends Command
{
    /**
     * Artisan コマンドの定義
     * 例: php artisan make:blade plugin MyPlugin admin/dashboard
     */
    protected $signature = 'make:blade
        {type : The type of blade to create (core|plugin|theme)}
        {identifier : The plugin or theme name (ignored if type=core)}
        {file : The blade file name (with optional subdirectories, e.g. home/index)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Blade template for core, plugin, or theme.';

    /**
     * blade.stub などのスタブファイル名
     */
    protected string $stubFileName = 'blade.stub';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1) 引数を取得
        $type       = $this->argument('type');       // "core"|"plugin"|"theme"
        $identifier = $this->argument('identifier'); // プラグイン名 or テーマ名 (core時は不要)
        $file       = $this->argument('file');       // 例) "admin/dashboard", "pages/home" など

        // 2) Bladeファイルの拡張子やパス区切りを調整
        //    "admin/dashboard" => "admin/dashboard.blade.php"
        //    "home/index" => "home/index.blade.php"
        //    "top" => "top.blade.php"
        if (! Str::endsWith($file, '.blade.php')) {
            $file .= '.blade.php';
        }

        // 3) 出力先パスを決める
        //    core => resources/views
        //    plugin => plugins/{identifier}/resources/views
        //    theme => themes/{identifier}/resources/views
        $basePath = match ($type) {
            'core'   => resource_path('views'),
            'plugin' => base_path("plugins/{$identifier}/resources/views"),
            'theme'  => base_path("themes/{$identifier}/resources/views"),
            default  => null,
        };

        // typeの指定が不正、あるいは plugin/theme なのにidentifierがない場合
        if (is_null($basePath)) {
            $this->error("Invalid type: {$type}. Must be one of [core, plugin, theme].");
            return Command::FAILURE;
        }
        if (($type === 'plugin' || $type === 'theme') && empty($identifier)) {
            $this->error("You must specify the {$type} name as second argument.");
            return Command::FAILURE;
        }

        // 4) 実際に保存するファイルパス
        $targetPath = $basePath . '/' . $file;

        // 5) 既に同名ファイルが存在するか？
        if (File::exists($targetPath)) {
            $this->error("Blade file [{$targetPath}] already exists.");
            return Command::FAILURE;
        }

        // 6) ディレクトリがなければ作成
        File::ensureDirectoryExists(dirname($targetPath), 0755, true);

        // 7) stubファイルからテンプレートを取得
        //    stubs/blade.stub を想定
        $stubPath = base_path("stubs/{$this->stubFileName}");


        if (! File::exists($stubPath)) {
            $this->error("Stub file [{$stubPath}] not found. Please create stubs/blade.stub.");
            return Command::FAILURE;
        }
        $stubContent = File::get($stubPath);

        // 8) Bladeコメント化されたライセンス文を取得
        $licenseBladeComment = $this->getLicenseBladeComment();


        // 9) ここで必要に応じてライセンス情報などを挿入したり、{{ placeholder }} を置換
        //    例えばプラグイン名やテーマ名、ソフトウェア名など
        $replacements = [
            '{{ type }}'       => $type,          // "core"|"plugin"|"theme"
            '{{ identifier }}' => $identifier,    // プラグイン名/テーマ名
            '{{ filename }}'   => $file,          // 例: "admin/dashboard.blade.php"
        ];
        $bladeContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stubContent
        );

        // 8-2) Bladeファイル先頭にライセンスコメントを挿入
        //      もし別の位置に入れたいなら、stub側で {{ license }} プレースホルダを作って
        //      str_replace('{{ license }}', $licenseBladeComment, $bladeContent)
        //      するのもOK
        // stub内に {{ license }} を記載 → そこを置換
        $bladeContent = str_replace('{{ license }}', $licenseBladeComment, $stubContent);


        // 9) ファイルを作成
        File::put($targetPath, $bladeContent);

        $this->info("Blade file created: [{$targetPath}]");

        return Command::SUCCESS;
    }

    /**
     * license.txt を読み込み、license-info.json で置換し、
     * PHPDoc形式コメント → Bladeコメント に変換して返す
     */
    protected function getLicenseBladeComment(): string
    {
        $licenseTxtPath  = base_path('license.txt');
        $licenseJsonPath = base_path('license-info.json');

        if (! file_exists($licenseTxtPath) || ! file_exists($licenseJsonPath)) {
            // ファイルが無い場合は空文字を返すか、例外を投げる
            return '';
        }

        // 1) license.txt 読み込み (PHPDoc形式 /** ... */)
        $licenseRaw = file_get_contents($licenseTxtPath);

        // 2) license-info.json 読み込み
        $jsonData = json_decode(file_get_contents($licenseJsonPath), true) ?: [];

        // 3) 変数を置換 ({software}, {author}, {website}, {year}など)
        $licenseWithVars = str_replace(
            ['{software}', '{author}', '{website}', '{year}'],
            [
                $jsonData['software'] ?? 'UnknownSoftware',
                $jsonData['author']   ?? 'UnknownAuthor',
                $jsonData['website']  ?? 'https://example.com',
                date('Y'),
            ],
            $licenseRaw
        );

        $licenseBlade = str_replace(
            ['/**', ' */'],
            ['{{--', '--}}'],
            $licenseWithVars
        );
        // 各行の先頭の*を削除
        $licenseBlade = preg_replace('/ \* ?/', '', $licenseBlade);

        $licenseBlade = preg_replace(
            ['/ \*\ /', '/ \*/'],
            ['', ''],
            $licenseBlade
        );



        return $licenseBlade;
    }
}
