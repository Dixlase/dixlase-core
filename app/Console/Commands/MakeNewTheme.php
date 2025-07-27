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
use Illuminate\Support\Facades\Artisan;

class MakeNewTheme extends Command
{
    protected $signature = 'make:theme {themeName? : command.make_theme.enter_theme_name}
        {--install : command.make_theme.confirm_install}
        {--activate : command.make_theme.confirm_activate}';
    
    protected $description = 'command.make_theme.description';


    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // ユーザーが入力したテーマ名（スペース等を含むオリジナル）
        $originalName = $this->argument('themeName');
        
        // テーマ名が指定されていない場合は入力を求める
        if (empty($originalName)) {
            $originalName = $this->ask(__('command.make_theme.enter_theme_name'));
            
            // 空の場合はエラー
            if (empty($originalName)) {
                $this->error(__('command.make_theme.name_cannot_be_empty'));
                return Command::FAILURE;
            }
        }

        // テーマ用ディレクトリ名 (キャメルケース化)
        $themeDirName = Str::studly($originalName);

        // スラッグ名。テーマ名をスネークケースに変換
        $slugName = Str::slug(Str::snake($originalName));

        // テーマ保存先のパス
        $themeDir = base_path("themes/{$themeDirName}");


        // 既に存在していたらエラー
        if (File::exists($themeDir)) {
            $this->error(__('command.make_theme.theme_exists', ['themeName' => $originalName]));
            return Command::FAILURE;
        }

        // テーマディレクトリ作成
        $this->createThemeDirectories($themeDir);

        // テーマ初期ファイルの生成
        $this->createThemeFiles($themeDir, $originalName, $themeDirName);

        $this->info(__('command.make_theme.created', ['themeName' => $originalName]));
        
        // インストール確認（オプション指定がない場合は確認）
        $shouldInstall = $this->option('install') || $this->confirm(__('command.make_theme.confirm_install'), true);
        
        if ($shouldInstall) {
            // インストールコマンドを実行
            $this->call('theme:install', [
                'themeName' => $slugName,
                '--force' => true
            ]);

            // 有効化確認（オプション指定がない場合は確認）
            $shouldActivate = $this->option('activate') || $this->confirm(__('command.make_theme.confirm_activate'), true);
            
            if ($shouldActivate) {
                // 有効化コマンドを実行
                $this->call('theme:activate', [
                    'themeName' => $slugName
                ]);
            }
        } else {
            $this->info(__('command.make_theme.install_later', ['slugName' => $slugName]));
        }
        return Command::SUCCESS;
    }



    /**
     * テーマディレクトリと初期ファイルを作成
     *
     * @param string $directory
     * @param string $themeName
     */
    protected function createThemeDirectories(string $themeDir): void
    {
        $directories = [
            'resources/views',
            'resources/src/js',
            'resources/src/css',
            'resources/assets/js',
            'resources/assets/css',
            'resources/assets/images',
        ];
        // ルートディレクトリの作成
        File::makeDirectory($themeDir, 0755, true);

        // 各サブディレクトリを作成
        foreach ($directories as $dir) {
            File::makeDirectory("{$themeDir}/{$dir}", 0755, true);
        }
    }

    /**
     * テーマ用のファイルをスタブベースで作成
     *
     * @param string $themeDir      テーマディレクトリのパス
     * @param string $themeName     ユーザーが入力したテーマの人間向け名称
     * @param string $themeDirName  テーマのディレクトリ名(ケバブケース)
     */
    protected function createThemeFiles(string $themeDir, string $themeName, string $themeDirName): void
    {
        // スタブファイルを探すパス
        $stubPath = config('console.custom_stub_paths');

        // 外部ファイルやDBなどからライセンス情報を取得
        $licenseContent = $this->fileGenerator->getLicenseContent();

        $licenseName = $this->fileGenerator->getLicenseName();

        // プレースホルダ定義
        $placeholders = [
            '{{ license }}'        => $licenseContent,  // ライセンス本文
            '{{ themeName }}'      => $themeName,       // 人間向け名称
            '{{ themeDirectory }}' => $themeDirName,    // ディレクトリ名
            '{{ themeLicense }}'   => $licenseName,     // ライセンス名
        ];

        // ***** vite.config.js *****
        $stubFile = $this->fileGenerator->getStubContent('vite.config.theme.stub', null, $stubPath);
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$themeDir}/vite.config.js", $fileContent);

        // ***** composer.json *****
        $stubFile = $this->fileGenerator->getStubContent('composer.theme.stub', null, $stubPath);
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$themeDir}/composer.json", $fileContent);

        // ***** index.blade.php *****
        // スタブを使わずに直接生成する例 (必要ならstubs化してもOK)
        $bladeContent = "<h1>Welcome to {$themeName} Theme</h1>";
        File::put("{$themeDir}/resources/views/index.blade.php", $bladeContent);

        // ***** デフォルトJS/SCSSなどの初期ファイル *****
        File::put("{$themeDir}/resources/src/js/app.js", "// JavaScript for {$themeDirName}");
        File::put("{$themeDir}/resources/src/css/style.css", "/* Styles for {$themeDirName} */");
    }
}
