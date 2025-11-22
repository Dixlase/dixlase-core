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
        {--enable : command.make_theme.confirm_enable}
        {--with-settings : command.make_theme.with_settings}';
    
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

        // 設定ページ作成の確認（オプション指定がない場合は確認）
        $withSettings = $this->option('with-settings') || $this->confirm(__('command.make_theme.confirm_with_settings'), false);

        // テーマディレクトリ作成
        $this->createThemeDirectories($themeDir, $withSettings);

        // テーマ初期ファイルの生成
        $this->createThemeFiles($themeDir, $originalName, $themeDirName, $slugName, $withSettings);

        $this->info(__('command.make_theme.created', ['themeName' => $originalName]));
        
        if ($withSettings) {
            $this->info(__('command.make_theme.settings_created'));
        }
        
        // インストール確認（オプション指定がない場合は確認）
        $shouldInstall = $this->option('install') || $this->confirm(__('command.make_theme.confirm_install'), true);
        
        if ($shouldInstall) {
            // インストールコマンドを実行
            $this->call('dls:theme:install', [
                'themeName' => $slugName
            ]);

            // マイグレーション実行（設定ページ作成時のみ）
            if ($withSettings) {
                $this->info(__('command.make_theme.running_migrations'));
                $this->call('dls:theme:migrate', [
                    'theme' => $themeDirName,
                    '--force' => true
                ]);

                // シーダー実行
                $this->info(__('command.make_theme.running_seeders'));
                $this->call('dls:theme:seed', [
                    'theme' => $themeDirName,
                    '--force' => true
                ]);
            }

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
     * @param string $themeDir
     * @param bool $withSettings
     */
    protected function createThemeDirectories(string $themeDir, bool $withSettings = false): void
    {
        $directories = [
            'app/Providers',
            'resources/views',
            'resources/src/js',
            'resources/src/css',
            'resources/assets/js',
            'resources/assets/css',
            'resources/assets/images',
            'routes',
            'lang/ja',
            'lang/en',
            'database/migrations',
            'database/seeders',
            'config',
        ];
        
        // 設定ページ用の追加ディレクトリ
        if ($withSettings) {
            $directories = array_merge($directories, [
                'app/Http/Controllers/Admin/Settings/Themes',
                'app/Http/Requests',
                'app/Models',
                'resources/views/admin/settings/themes',
            ]);
        }
        
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
     * @param string $slugName      スラッグ名
     * @param bool $withSettings    設定ページを作成するか
     */
    protected function createThemeFiles(string $themeDir, string $themeName, string $themeDirName, string $slugName, bool $withSettings = false): void
    {
        // ライセンス情報を取得（デフォルトはGPL）
        $selectedLicense = $this->getNewLicenseInfo(false, 'GPL');
        $licenseTemplate = $selectedLicense['template'] ?? '';
        $licenseName = $selectedLicense['info']['licenseName'] ?? 'GPL-3.0';

        // ライセンス情報を更新
        $licenseInfo = array_merge($selectedLicense['info'] ?? [], [
            'software' => $themeName,
            'author' => 'Your Name',
            'email' => 'your-email@example.com',
            'url' => 'https://example.com',
            'year' => date('Y'),
        ]);

        // ライセンステキストをプレースホルダーで置換
        $licenseText = $this->replacePlaceholders($licenseTemplate, $licenseInfo);

        // ライセンステキストをPHPコメント形式に変換
        $licenseContent = $this->embedLicenseForPhp($licenseText);

        // プレースホルダ定義
        $placeholders = [
            'license'           => $licenseContent,
            'themeName'         => $themeName,
            'themeDirectory'    => $themeDirName,
            'themeLicense'      => $licenseName,
            'licenseName'       => $licenseName,
            'licenseTemplate'   => 'license-' . strtolower(str_replace([' ', '.'], ['-', ''], $licenseName)) . '.txt',
            'packageName'       => Str::slug($themeName),
            'slug'              => $slugName,
            'author'            => 'Your Name',
            'email'             => 'your-email@example.com',
            'url'               => 'https://example.com',
            'year'              => date('Y'),
            'licenseFullText'   => $licenseContent,
            'namespace'         => "Themes\\{$themeDirName}",
            'tablePrefix'       => 'thm_' . Str::snake($slugName) . '_',
        ];

        // ***** 基本ファイル *****
        $this->createFileFromStub('vite.config.theme.stub', "{$themeDir}/vite.config.js", $placeholders);
        $this->createFileFromStub('theme.json.stub', "{$themeDir}/theme.json", $placeholders);
        $this->createFileFromStub('composer.theme.stub', "{$themeDir}/composer.json", $placeholders);
        $this->createFileFromStub('package.theme.stub', "{$themeDir}/package.json", $placeholders);

        // ***** README.md *****
        $readmeContent = "# {$themeName}\n\nA custom theme for Dixlase.\n\n## Installation\n\n```bash\nphp artisan dls:theme:install {$slugName}\n```\n";
        File::put("{$themeDir}/README.md", $readmeContent);

        // ***** license-info.json *****
        $licenseInfoContent = json_encode([
            'license' => $licenseName,
            'licenseName' => $licenseName,
            'software' => $themeName,
            'author' => 'Your Name',
            'email' => 'your-email@example.com',
            'url' => 'https://example.com',
            'year' => date('Y'),
            'template' => $licenseTemplate,
            'info' => $licenseInfo
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        File::put("{$themeDir}/license-info.json", $licenseInfoContent);

        // ***** サービスプロバイダー *****
        $this->createServiceProvider($themeDir, $themeDirName, $withSettings, $placeholders);

        // ***** ルートファイル *****
        $this->createRouteFiles($themeDir, $themeDirName, $withSettings, $placeholders);

        // ***** ビューファイル *****
        $bladeContent = "<h1>Welcome to {$themeName} Theme</h1>";
        File::put("{$themeDir}/resources/views/index.blade.php", $bladeContent);

        // ***** 言語ファイル *****
        $this->createLanguageFiles($themeDir, $themeName, $withSettings);

        // ***** デフォルトJS/CSS *****
        File::put("{$themeDir}/resources/src/js/app.js", "// JavaScript for {$themeDirName}\nconsole.log('{$themeName} theme loaded');");
        File::put("{$themeDir}/resources/src/css/style.css", "/* Styles for {$themeDirName} */\n\nbody {\n    font-family: sans-serif;\n}");

        // ***** 設定ページ関連ファイル *****
        if ($withSettings) {
            $this->createSettingsFiles($themeDir, $themeDirName, $slugName, $placeholders);
        }
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

    /**
     * サービスプロバイダーを作成
     */
    protected function createServiceProvider(string $themeDir, string $themeDirName, bool $withSettings, array $placeholders): void
    {
        $this->createFileFromStub(
            'theme-service-provider.stub',
            "{$themeDir}/app/Providers/{$themeDirName}ServiceProvider.php",
            $placeholders
        );
    }

    /**
     * ルートファイルを作成
     */
    protected function createRouteFiles(string $themeDir, string $themeDirName, bool $withSettings, array $placeholders): void
    {
        $this->createFileFromStub(
            'theme-route-web.stub',
            "{$themeDir}/routes/web.php",
            $placeholders
        );

        if ($withSettings) {
            $this->createFileFromStub(
                'theme-route-admin.stub',
                "{$themeDir}/routes/admin.php",
                $placeholders
            );
        }
    }

    /**
     * 言語ファイルを作成
     */
    protected function createLanguageFiles(string $themeDir, string $themeName, bool $withSettings): void
    {
        $placeholders = ['themeName' => $themeName];
        
        $this->createFileFromStub(
            'theme-lang.stub',
            "{$themeDir}/lang/ja/theme.php",
            $placeholders
        );
        
        $this->createFileFromStub(
            'theme-lang.stub',
            "{$themeDir}/lang/en/theme.php",
            $placeholders
        );
    }

    /**
     * 設定ページ関連ファイルを作成
     */
    protected function createSettingsFiles(string $themeDir, string $themeDirName, string $slugName, array $placeholders): void
    {
        // Controller
        $this->createFileFromStub(
            'theme-settings-controller.stub',
            "{$themeDir}/app/Http/Controllers/Admin/Settings/Themes/ThemeSettingsController.php",
            $placeholders
        );

        // Settings view
        $this->createFileFromStub(
            'theme-settings-view.stub',
            "{$themeDir}/resources/views/admin/settings/themes/settings.blade.php",
            $placeholders
        );

        // Config file for admin navigation
        $this->createFileFromStub(
            'theme-config-admin.stub',
            "{$themeDir}/config/admin.php",
            $placeholders
        );

        // Model
        $this->createFileFromStub(
            'theme-settings-model.stub',
            "{$themeDir}/app/Models/ThemeSetting.php",
            $placeholders
        );

        // Migration
        $migrationFileName = '0001_01_01_000100_create_' . $placeholders['tablePrefix'] . 'settings_table.php';
        $this->createFileFromStub(
            'theme-settings-migration.stub',
            "{$themeDir}/database/migrations/{$migrationFileName}",
            $placeholders
        );

        // Seeder
        $this->createFileFromStub(
            'theme-settings-seeder.stub',
            "{$themeDir}/database/seeders/ThemeSettingsSeeder.php",
            $placeholders
        );

        // Database Seeder
        $this->createFileFromStub(
            'theme-database-seeder.stub',
            "{$themeDir}/database/seeders/DatabaseSeeder.php",
            $placeholders
        );
    }
}
