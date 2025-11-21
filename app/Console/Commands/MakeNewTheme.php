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
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;

class MakeNewTheme extends Command
{
    use MakeLicenseTrait, MakeFileTrait;

    protected $signature = 'dls:make:theme {themeName? : command.make_theme.enter_theme_name}
        {--install : command.make_theme.confirm_install}
        {--enable : command.make_theme.confirm_enable}';
    
    protected $description = 'command.make_theme.description';

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
            $this->call('dls:theme:install', [
                'themeName' => $slugName,
                '--force' => true
            ]);

            // 有効化確認（オプション指定がない場合は確認）
            $shouldEnable = $this->option('enable') || $this->confirm(__('command.make_theme.confirm_enable'), true);
            
            if ($shouldEnable) {
                // 有効化コマンドを実行
                $this->call('dls:theme:enable', [
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
     * @param string $themeDirName  テーマのディレクトリ名(StudlyCase)
     */
    protected function createThemeFiles(string $themeDir, string $themeName, string $themeDirName): void
    {
        // ライセンス情報を取得（デフォルトはAGPL）
        $selectedLicense = $this->getNewLicenseInfo(false, 'GPL');
        $licenseContent = $selectedLicense['template'] ?? '';
        $licenseName = $selectedLicense['info']['licenseName'] ?? 'GPL-3.0';

        // プレースホルダ定義
        $placeholders = [
            'license'           => $licenseContent,
            'themeName'         => $themeName,
            'themeDirectory'    => $themeDirName,
            'themeLicense'      => $licenseName,
            'licenseName'       => $licenseName,
            'licenseTemplate'   => 'license-' . strtolower(str_replace([' ', '.'], ['-', ''], $licenseName)) . '.txt',
            'packageName'       => Str::slug($themeName),
            'slug'              => Str::slug($themeName),
            'author'            => 'Your Name',
            'email'             => 'your-email@example.com',
            'url'               => 'https://example.com',
            'year'              => date('Y'),
            'licenseFullText'   => $licenseContent,
        ];

        // ***** vite.config.js *****
        $this->createFileFromStub(
            'vite.config.theme.stub',
            "{$themeDir}/vite.config.js",
            $placeholders
        );

        // ***** theme.json *****
        $this->createFileFromStub(
            'theme.json.stub',
            "{$themeDir}/theme.json",
            $placeholders
        );

        // ***** composer.json *****
        $this->createFileFromStub(
            'composer.theme.stub',
            "{$themeDir}/composer.json",
            $placeholders
        );

        // ***** package.json *****
        $this->createFileFromStub(
            'package.theme.stub',
            "{$themeDir}/package.json",
            $placeholders
        );

        // ***** README.md *****
        $readmeContent = "# {$themeName}\n\nA custom theme for Dixlase.\n\n## Installation\n\n```bash\nphp artisan theme:install " . Str::slug($themeName) . "\n```\n";
        File::put("{$themeDir}/README.md", $readmeContent);

        // ***** index.blade.php *****
        $bladeContent = "<h1>Welcome to {$themeName} Theme</h1>";
        File::put("{$themeDir}/resources/views/index.blade.php", $bladeContent);

        // ***** デフォルトJS/CSSなどの初期ファイル *****
        File::put("{$themeDir}/resources/src/js/app.js", "// JavaScript for {$themeDirName}\nconsole.log('{$themeName} theme loaded');");
        File::put("{$themeDir}/resources/src/css/style.css", "/* Styles for {$themeDirName} */\n\nbody {\n    font-family: sans-serif;\n}");
    }

    /**
     * スタブファイルからファイルを作成
     *
     * @param string $stubFileName スタブファイル名
     * @param string $outputPath 出力先パス
     * @param array $placeholders プレースホルダー
     */
    protected function createFileFromStub(string $stubFileName, string $outputPath, array $placeholders): void
    {
        $stubPath = config('command.custom_stub_directory') . '/' . $stubFileName;
        
        if (!File::exists($stubPath)) {
            $this->warn("Stub file not found: {$stubFileName}");
            return;
        }

        $content = File::get($stubPath);
        $content = $this->replacePlaceholders($content, $placeholders);
        
        File::put($outputPath, $content);
    }
}
