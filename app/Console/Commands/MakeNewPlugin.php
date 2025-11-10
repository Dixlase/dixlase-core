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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Plugin;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;

class MakeNewPlugin extends Command
{
    use MakeLicenseTrait, MakeFileTrait;

    /**
     * コマンドシグネチャ
     *  - {name} : プラグイン名（人間が読む名 + ディレクトリに利用）
     *  - --namespace : 追加したいトップレベル名前空間 (例: Vendor)
     *  - --vendor : Composerパッケージのベンダー名 (デフォルト "plugins")
     *  - --install, --enable : インストール＆有効化フラグ
     */

    protected $signature = 'make:plugin
                            {pluginName? : The name of the plugin}
                            {--author= : The author of the plugin}
                            {--email= : The email address of the author}
                            {--website= : The website URL for the plugin}
                            {--license= : The license type (GPL, AGPL, MIT, Apache, BSD, LGPL, commercial, custom, none)}
                            {--install : Install the plugin after creation}
                            {--enable : Enable the plugin after installation (implies --install)}';

    protected $description = 'Create a new plugin with a predefined structure';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ensure the URL has a proper scheme (adds https:// if missing)
     *
     * @param string $url
     * @return string
     */
    protected function ensureUrlHasScheme(string $url): string
    {
        if (empty($url)) {
            return $url;
        }

        // Remove any existing scheme
        $url = preg_replace('#^https?://#', '', $url);
        
        return 'https://' . ltrim($url, '/');
    }

    public function handle()
    {

        // ソフトウェア名
        $softwareName = config('app.name');
        // ソフトウェア名をスネークケースに変換
        $cmsNameSlug = Str::slug(Str::snake($softwareName));
        // プラグイン名を取得（引数がなければ入力を求める）
        $pluginName = $this->argument('pluginName') ?: $this->ask(__('command.make_plugin.enter_plugin_name'));
        // スラッグ名。プラグイン名をスネークケースに変換
        $pluginSlug = Str::slug(Str::snake($pluginName));
        // プラグインディレクトリ名。プラグイン名をキャメルケースに変換
        $pluginDirName = Str::studly($pluginName);
        // プラグイン保存先のパス
        $pluginDir = base_path("plugins/{$pluginDirName}");
        // プラグインの名前空間
        $namespace = "Plugins\\{$pluginDirName}";
        // ベンダー名 (Composerパッケージ用)
        $vendorName = 'plugins';
        // Composerパッケージ名
        $packageName = "{$cmsNameSlug}-{$pluginSlug}";


        if (File::exists($pluginDir)) {
            $this->error(__('command.make_plugin.already_exists', ['pluginName' => $pluginName]));
            return Command::FAILURE;
        }

        // 開発者情報の取得
        $author = $this->option('author') ?: $this->ask(__('command.make_plugin.enter_author_name') . ' (optional)');

        // メールアドレスの入力を求める（オプションで指定されていればそれを使用、なければ入力を求める）
        $email = $this->option('email') ?: $this->ask(__('command.make_plugin.enter_email') . ' (optional)', 'your-email@example.com');

        // URL入力を補完（https://を自動追加）
        $defaultWebsite = 'example.com';
        $websitePrompt = $this->option('website') ?: $this->ask(__('command.make_plugin.enter_website_url') . ' (optional)' , $defaultWebsite);
        $website = $websitePrompt === $defaultWebsite ? $websitePrompt : $this->ensureUrlHasScheme($websitePrompt);


        // ライセンス情報を取得（オプションで指定されていればそれを使用、なければ選択を求める）
        $licenseKey = $this->option('license');
        $selectedLicense = $this->getNewLicenseInfo(false, $licenseKey);
        
        // ライセンス情報が空でない場合は、アプリケーション名や著者情報を更新
        if (!empty($selectedLicense)) {
            $selectedLicense['info']['software'] = $pluginName;
            $selectedLicense['info']['author'] = $author;
            $selectedLicense['info']['email'] = $email;
            $selectedLicense['info']['website'] = $website;
        }


        // デフォルトのライセンス情報を設定
        $licenseInfo = [
            'software' => $pluginName,
            'author' => $author,
            'email' => $email,
            'website' => $website,
        ];
    
        
        if (!Str::startsWith($website, 'https://')) {
            $website = 'https://' . ltrim($website, '/');
        }

        $licenseInfo['website'] = $website;

    
        // 選択されたライセンス情報をマージ
        if (!empty($selectedLicense['info'])) {
            $licenseInfo = array_merge($selectedLicense['info'], $licenseInfo);
        }

        $licenseInfo['licenseText'] = $this->replacePlaceholders($selectedLicense['template'], $licenseInfo);
        


        // プラグインディレクトリを作成
        $this->createPluginDirectories($pluginName, $pluginDir, $namespace, $pluginDirName);

        // スタブファイルを使って各種ファイルを生成
        $this->createPluginFiles(
            $pluginDir,
            $pluginName,
            $pluginDirName,
            $vendorName,
            $namespace,
            $cmsNameSlug,
            $pluginSlug,
            $softwareName,
            $author,
            $email,
            $website,
            $licenseInfo
        );

        // Handle plugin installation and enabling based on options
        $shouldInstall = $this->option('install') || $this->option('enable');
        $shouldEnable = $this->option('enable');
        
        if ($shouldInstall || $shouldEnable) {
            // If --enable is specified, it implies --install
            if ($shouldEnable) {
                // 有効化を指定してインストールを実行
                $this->installPlugin($pluginName, $pluginDirName, true);
            } else if ($shouldInstall) {
                // 有効化せずにインストールのみ実行
                $this->installPlugin($pluginName, $pluginDirName, false);
            }
        } else if ($this->confirm(__('command.make_plugin.confirm_install'), true)) {
            // インタラクティブモードでインストールを実行
            $shouldEnableAfterInstall = $this->confirm(__('command.make_plugin.confirm_enable'), true);
            $this->installPlugin($pluginName, $pluginDirName, $shouldEnableAfterInstall);
        }

        // composer.local.jsonを更新（全プラグインを自動検出して同期）
        if (ComposerLocalHelper::syncAutoload()) {
            $this->info("✓ プラグイン '{$pluginDirName}' を composer.local.json に追加しました");
        } else {
            $this->warn("⚠ プラグイン '{$pluginDirName}' の composer.local.json への追加に失敗しました");
        }

        // .git/info/excludeにプラグインを追加
        if (GitExcludeHelper::addPluginExclusion($pluginDirName)) {
            $this->info("✓ プラグイン '{$pluginDirName}' を .git/info/exclude に追加しました");
        } else {
            $this->warn("⚠ プラグイン '{$pluginDirName}' の .git/info/exclude への追加に失敗しました");
        }

        // 注意: composer.local.jsonのみ更新し、composer.jsonは素の状態を保持
        // オートロードの反映は `composer dump-autoload` で手動実行

        $this->info(__('command.make_plugin.success', ['pluginName' => $pluginName]));
        return Command::SUCCESS;
    }

    protected function createPluginDirectories(string $pluginName, string $pluginDir, string $namespace, string $pluginDirName)
    {
        $directories = [
            // Laravelアプリケーション関連
            "app/Http/Controllers",
            "app/Http/Middleware",
            "app/Http/Requests",
            "app/Models",
            "app/Providers",
            "app/Traits",       // 共通メソッドを切り出すTrait用
            "app/Console",      // プラグイン独自のコマンドを置く場合
            "app/Helpers",      // ヘルパー関数等を置く場合

            // 設定・ルート
            "config",
            "routes",

            // リソース・テンプレート関連
            "resources/views",

            // テストディレクトリ
            "tests/Feature",
            "tests/Unit",

            // 言語ファイル
            "lang/en",
            "lang/ja",
            
            // マイグレーションやファクトリなど
            "database/migrations",
            "database/factories",
            "database/seeders",

            // アセット関連
            "resources/assets/js",
            "resources/assets/css",
            "resources/assets/images",

            // フロントエンドの元ソース
            "resources/src/js",
            "resources/src/css",
            "resources/src/images",
        ];

        // ディレクトリを作成
        foreach ($directories as $dir) {
            File::makeDirectory("{$pluginDir}/{$dir}", 0755, true);
        }
    }
    // 以下は、createPluginDirectories()内で生成したファイルを生成するメソッド
    protected function createPluginFiles(
        string $pluginDir,
        string $pluginName,
        string $pluginDirName,
        string $vendorName,
        string $namespace,
        string $cmsNameSlug,
        string $pluginSlug,
        string $softwareName,
        string $author,
        string $email,
        string $website,
        array $licenseInfo
    ) {


        // 例: vendorName が null の場合や空文字の場合に 'plugins' をデフォルトとする
        $vendorNameDefault = $vendorName ?: 'plugins';

        // vendorName を StudlyCase に
        $vendorNameStudly = Str::studly($vendorNameDefault);

        // プラグイン名からスペースを除去してからStudlyCaseに変換
        $pluginNameNoSpaces = str_replace(' ', '', $pluginName);
        $pluginNameStudly = Str::studly($pluginNameNoSpaces);

        // ライセンス情報を取得
        $licenseName = $licenseInfo['license'] ?? '';

        

        $placeholders = [
            'pluginName'        => $pluginName,
            'pluginNameStudly'  => $pluginNameStudly,
            'pluginDirName'     => $pluginDirName,
            'namespace'         => $namespace,
            'pluginSlug'        => $pluginSlug,
            'licenseName'       => $licenseName,
            'vendorName'        => $vendorName,
            'vendorNameDefault' => $vendorNameDefault,
            'vendorNameStudly'  => $vendorNameStudly,
            'softwareName'      => $softwareName,
            'cmsNameSlug'       => $cmsNameSlug,
            'author'            => $author,
            'email'             => $email,
            'website'           => $website,
        ];


        // 初期ファイルを作成（js/css）
        File::put("{$pluginDir}/resources/src/js/app.js", "// JavaScript for {$pluginDirName}");
        File::put("{$pluginDir}/resources/src/css/style.scss", "/* SCSS for {$pluginDirName} */");


        // プラグインのメインファイルを作成
        
        // プラグイン専用の license-info.json を作成
        $this->createLicenseInfoFile($pluginName, $pluginDir, $licenseInfo);

        // コンフィグファイルを作成
        $this->createConfigFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // ルートファイルを作成
        $this->createRoutes($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // 言語ファイルを作成
        $this->createLangFiles($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // Vite 設定ファイルを作成
        $this->createViteConfigFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // composer.json を作成
        $this->createComposerFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // README.md を作成
        $this->createReadmeFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // .editorconfig を作成
        $this->createEditorConfigFile($pluginDir, $placeholders);

        // サービスプロバイダを生成
        $this->createServiceProvider($pluginName, $pluginDirName, $licenseInfo);
        
        // DatabaseSeederを作成
        $this->createDatabaseSeeder($pluginName, $pluginDir, $namespace, $licenseInfo);
        
        // PHPUnit 設定ファイルを作成
        $this->createPhpUnitConfig($pluginDir, $placeholders);
        
    }


    /**
     * ライセンス情報ファイルを作成
     *
     * @param string $pluginDir プラグインディレクトリ
     * @param array $licenseInfo ライセンス情報
     * @return void
     */
    protected function createLicenseInfoFile(string $pluginName, string $pluginDir, array $licenseInfo)
    {
        // ライセンス名とテンプレートを適切に設定
        $licenseName = $licenseInfo['license'] ?? 'GPL';
        
        // テンプレートがフルパスやテキストを含む場合、ファイル名のみを抽出
        $template = $licenseInfo['template'] ?? 'license-' . strtolower(str_replace([' ', '.'], ['-', ''], $licenseName)) . '.txt';
        if (str_contains($template, DIRECTORY_SEPARATOR)) {
            $template = basename($template);
        }
        
        // ライセンスデータを構築
        $licenseData = [
            'software' => $pluginName,
            'author' => $licenseInfo['author'] ?? 'My Company',
            'email' => $licenseInfo['email'] ?? 'company@example.com',
            'website' => $licenseInfo['website'] ?? 'https://example.com',
            'license' => $licenseName,
            'template' => $template
        ];

        // JSONファイルに保存
        $jsonContent = json_encode($licenseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents("{$pluginDir}/license-info.json", $jsonContent);
        
        $this->info(__('command.make_plugin.files.license_info', ['pluginName' => $pluginName]));
    }

    /**
     * ルートファイルを作成
     */
    protected function createRoutes(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // プラグイン名をStudlyCaseに変換
        $pluginDirName = Str::studly($pluginName);
        
        // プレースホルダーをJSON形式にエンコード
        $placeholdersJson = json_encode($placeholders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // ライセンスキーを取得
        $licenseKey = $licenseInfo['info']['license'] ?? 'MIT';
        
        //フロント用のルートファイルを作成        
        Artisan::call('make:plugin:route', [
            'className' => "web",
            'pluginName' => $pluginDirName,
            'routeType' => 'web',
            '--placeholders' => $placeholdersJson,
            '--license-info' => $licenseKey,
        ]);

        //管理画面用のルートファイルを作成
        Artisan::call('make:plugin:route', [
            'className' => "admin",
            'pluginName' => $pluginDirName,
            'routeType' => 'admin',
            '--placeholders' => $placeholdersJson,
            '--license-info' => $licenseKey,
        ]);

        $this->info(__('command.make_plugin.files.routes', ['className' => "web.php, admin.php", 'pluginName' => $pluginName]));
    }

    

    /**
     * コンフィグファイルを作成
     */
    protected function createConfigFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // プラグイン名をStudlyCaseに変換
        $pluginDirName = Str::studly($pluginName);
        
        // Convert plugin name to snake_case for the config file name
        $configFileName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $pluginName));

        // プレースホルダーをJSON形式にエンコード
        $placeholdersJson = json_encode($placeholders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // ライセンスキーを取得
        $licenseKey = $licenseInfo['info']['license'] ?? 'MIT';

        // コンフィグファイルを作成        
        Artisan::call('make:plugin:config', [
            'className' => $configFileName,
            'pluginName' => $pluginDirName,
            '--placeholders' => $placeholdersJson,
            '--license-info' => $licenseKey,
        ]);

        $this->info(__('command.make_plugin.files.config', ['pluginName' => $pluginName]));
    }

    /**
     * 言語ファイルを作成
     */
    protected function createLangFiles(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // プラグイン名をStudlyCaseに変換
        $pluginDirName = Str::studly($pluginName);
        
        // Convert plugin name to snake_case for the language file name
        $langFileName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $pluginName));

        // プレースホルダーをJSON形式にエンコード
        $placeholdersJson = json_encode($placeholders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // ライセンスキーを取得
        $licenseKey = $licenseInfo['info']['license'] ?? 'MIT';

        // 英語の言語ファイルを作成        
        Artisan::call('make:plugin:lang', [
            'className' => $langFileName,
            'pluginName' => $pluginDirName,
            'lang' => 'en',
            '--placeholders' => $placeholdersJson,
            '--license-info' => $licenseKey,
        ]);

        // 日本語の言語ファイルを作成
        Artisan::call('make:plugin:lang', [
            'className' => $langFileName,
            'pluginName' => $pluginDirName,
            'lang' => 'ja',
            '--placeholders' => $placeholdersJson,
            '--license-info' => $licenseKey,
        ]);

        $this->info(__('command.make_plugin.files.lang', ['pluginName' => $pluginName]));
    }

    /**
     * Viteの設定ファイルを作成
     */
    protected function createViteConfigFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('vite.config.plugin.stub', $placeholders);
        file_put_contents("{$pluginDir}/vite.config.js", $content);

        $this->info(__('command.make_plugin.files.vite', ['pluginName' => $pluginName]));
    }

    /**
     * composer.json を作成
     */
    protected function createComposerFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('composer.plugin.stub', $placeholders);
        file_put_contents("{$pluginDir}/composer.json", $content);

        $this->info(__('command.make_plugin.files.composer', ['pluginName' => $pluginName]));
    }

    /**
     * README.md を作成
     */
    protected function createReadmeFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        //現在の年を取得
        $placeholders['year'] = date('Y');
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('readme.plugin.stub', $placeholders);
        file_put_contents("{$pluginDir}/README.md", $content);

        $this->info(__('command.make_plugin.files.readme', ['pluginName' => $pluginName]));
    }



    /**
     * サービスプロバイダを作成
     */
    protected function createServiceProvider(string $pluginName, string $pluginDirName, array $licenseInfo)
    {
        $providerName = "{$pluginDirName}ServiceProvider";

        // ライセンスキーを取得
        $licenseKey = $licenseInfo['info']['license'] ?? 'MIT';

        $params = [
            'className' => $providerName,
            'pluginName' => $pluginDirName, // StudlyCase形式のディレクトリ名を使用
            '--license-info' => $licenseKey,
        ];
        
        Artisan::call('make:plugin:provider', $params);

        $this->info(__('command.make_plugin.files.service_provider', ['className' => $providerName, 'pluginName' => $pluginName]));
    }

    /**
     * プラグイン用のDatabaseSeederを作成
     *
     * @param string $pluginName プラグイン名
     * @param string $pluginDir プラグインディレクトリのフルパス
     * @param string $namespace プラグインの名前空間
     * @param array $licenseInfo ライセンス情報
     * @return void
     */
    protected function createDatabaseSeeder(string $pluginName, string $pluginDir, string $namespace, array $licenseInfo)
    {
        $seederPath = "{$pluginDir}/database/seeders/DatabaseSeeder.php";
        
        // 既に存在する場合は上書きしない
        if (File::exists($seederPath)) {
            return;
        }
        
        // ライセンス情報を取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName);
        
        // ライセンス情報が有効な場合は処理を続行
        if ($licenseInfo && !empty($licenseInfo['template'])) {
            // プレースホルダを置換するためのデータを準備
            $placeholders = [
                'software' => $licenseInfo['info']['software'] ?? config('app.name', 'Dixlase'),
                'year' => $licenseInfo['info']['year'] ?? date('Y'),
                'author' => $licenseInfo['info']['author'] ?? config('app.name', 'Dixlase'),
                'website' => $licenseInfo['info']['website'] ?? ''
            ];
            
            // 1. プレースホルダを置換
            $licenseTemplate = $this->replacePlaceholders($licenseInfo['template'], $placeholders);
            
            // 2. PHP用にコメントアウト
            $licenseHeader = $this->embedLicenseForPhp($licenseTemplate);
        } else {
            $licenseHeader = '';
        }
        
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('database-seeder.stub', [
            'namespace' => "{$namespace}\\Database\\Seeders",
            'license' => $licenseHeader
        ]);
        
        // ディレクトリが存在することを確認
        $seederDir = dirname($seederPath);
        if (!File::exists($seederDir)) {
            File::makeDirectory($seederDir, 0755, true);
        }
        
        file_put_contents($seederPath, $content);
        
        // クラス名を取得（パスから抽出）
        $seederName = 'DatabaseSeeder';
        
        // ローカライズされたメッセージを表示
        $this->info(__('command.make_plugin.files.database_seeder', [
            'className' => $seederName,
            'pluginName' => $pluginName
        ]));
    }
    
    /**
     * Create PHPUnit configuration file for the plugin
     *
     * @param string $pluginDir
     * @param array $placeholders
     * @return void
     */
    protected function createPhpUnitConfig(string $pluginDir, array $placeholders)
    {
        $phpunitPath = "{$pluginDir}/phpunit.xml";
        
        // 既に存在する場合は上書きしない
        if (File::exists($phpunitPath)) {
            return;
        }
        
        // Use the getStubContent method to read and process the stub
        $content = $this->getStubContent('phpunit.xml.stub', $placeholders, 'custom');
        
        if ($content === false) {
            $this->error('Failed to generate PHPUnit configuration');
            return;
        }
        
        File::put($phpunitPath, $content);
        
        // Create tests directory structure
        File::ensureDirectoryExists("{$pluginDir}/tests/Unit", 0755, true);
        File::ensureDirectoryExists("{$pluginDir}/tests/Feature", 0755, true);
        
        // Create placeholder test files if they don't exist
        $this->createExampleTestFile(
            "{$pluginDir}/tests/Unit/ExampleTest.php",
            $placeholders['namespace'] . '\\Tests\\Unit',
            'PHPUnit\\Framework\\TestCase'
        );
        
        $this->createExampleTestFile(
            "{$pluginDir}/tests/Feature/ExampleTest.php",
            $placeholders['namespace'] . '\\Tests\\Feature',
            'Tests\\TestCase'
        );
        
        $this->info(__('command.make_plugin.files.phpunit_config', [
            'pluginName' => basename($pluginDir)
        ]));
    }
    
    /**
     * Create an example test file
     *
     * @param string $path
     * @param string $namespace
     * @param string $testCase
     * @return void
     */
    /**
     * Create an example test file using stub templates
     *
     * @param string $path
     * @param string $namespace
     * @param string $testCase
     * @return void
     */
    /**
     * .editorconfig ファイルを作成
     *
     * @param string $pluginDir プラグインディレクトリ
     * @param array $placeholders プレースホルダ
     * @return void
     */
    protected function createEditorConfigFile(string $pluginDir, array $placeholders)
    {
        $editorConfigPath = "{$pluginDir}/.editorconfig";
        
        // 既に存在する場合は上書きしない
        if (File::exists($editorConfigPath)) {
            return;
        }
        
        // スタブファイルからコンテンツを取得
        $content = $this->getStubContent('editorconfig.stub', $placeholders, 'custom');
        
        if ($content === false) {
            $this->error('Failed to generate .editorconfig file');
            return;
        }
        
        // ファイルを作成
        File::put($editorConfigPath, $content);
        
        $this->info(__('command.make_plugin.files.editorconfig', [
            'pluginName' => basename($pluginDir)
        ]));
    }

    protected function createExampleTestFile(string $path, string $namespace, string $testCase)
    {
        if (!File::exists($path)) {
            // Determine which stub to use based on test type
            $isUnitTest = str_contains($path, 'Unit/');
            $stubFile = $isUnitTest ? 'test.unit.stub' : 'test.stub';
            
            // Get the class name from the path
            $className = basename($path, '.php');
            
            // Prepare placeholders
            $placeholders = [
                'namespace' => $namespace,
                'class' => $className,
                'testCase' => $testCase,
                'license' => $this->licenseInfo['licenseText'] ?? ''
            ];
            
            // Get the stub content using the existing method
            $content = $this->getStubContent($stubFile, $placeholders, 'custom');
            
            if ($content === false) {
                $this->error("Failed to generate test file: " . $path);
                return;
            }
            
            // Ensure the directory exists
            File::ensureDirectoryExists(dirname($path));
            
            // Write the test file
            File::put($path, $content);
            
            $this->info(sprintf('Created test file: %s', $path));
        }
    }


    
    /**
     * プラグインをインストール (DBに登録）
     * PluginInstallコマンドを利用してインストールを実行
     *
     * @param string $pluginName プラグイン名
     * @param string $pluginDirName プラグインディレクトリ名
     * @return void
     */
    /**
     * プラグインをインストール (DBに登録）
     * PluginInstallコマンドを利用してインストールを実行
     *
     * @param string $pluginName プラグイン名
     * @param string $pluginDirName プラグインディレクトリ名
     * @param bool $enable インストール後に有効化するかどうか
     * @return void
     */
    protected function installPlugin($pluginName, $pluginDirName, $enable = false)
    {
        // PluginInstallコマンドを実行（--enableオプションで有効化を制御）
        $this->call('plugin:install', [
            'pluginName' => $pluginDirName,
            '--enable' => $enable, // 明示的に有効化を指定した場合のみ有効化
        ]);
    }

    /**
     * プラグインを有効化 (DBでstatusを1に変更)
     * PluginEnableコマンドを利用して有効化を実行
     *
     * @param string $pluginName 有効化するプラグイン名
     * @return void
     */
    protected function enablePlugin($pluginName)
    {
        $this->call('plugin:enable', [
            'name' => $pluginName
        ]);
    }
}
