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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Plugin;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;

class MakePlugin extends Command
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
                            {--controller : Create a controller for the plugin}
                            {--model : Create a model for the plugin}
                            {--migration : Create a migration file for the plugin}
                            {--policy : Create a policy file for the plugin}
                            {--listener : Create an event listener for the plugin}
                            {--test : Create test cases for the plugin}
                            {--command : Create a command for the plugin}
                            {--job : Create a job for the plugin}
                            {--notification : Create a notification for the plugin}
                            {--resource : Create a resource for the plugin}
                            {--factory : Create a factory for the plugin}
                            {--seeder : Create a seeder for the plugin}
                            {--all : Create all of the above}';

    protected $description = 'Create a new plugin with a predefined structure';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        // ソフトウェア名
        $softwareName = config('app.name');
        // ソフトウェア名をスネークケースに変換
        $cmsNameSlug = Str::slug(Str::snake($softwareName));
        // プラグイン名
        $pluginName = $this->ask(__('command.make_plugin.enter_plugin_name'));
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
            $this->error(__('command.make_plugin.already_exists', ['name' => $pluginName]));
            return Command::FAILURE;
        }

        // 新規ライセンス情報を取得
        $selectedLicense = $this->getNewLicenseInfo(false);

        // デフォルトのライセンス情報を設定
        $licenseInfo = [
            'software' => $pluginName,
            'author' => '',
            'website' => '',
        ];
        
        // 開発者情報の取得
        $author = $this->ask(__('command.make_plugin.enter_author_name'));
        $licenseInfo['author'] = $author;

        // URL入力を補完（https://を自動追加）
        $defaultWebsite = 'example.com';
        $website = $this->ask(__('command.make_plugin.enter_website_url') . ' [' . $defaultWebsite . ']');
        
        if (!Str::startsWith($website, 'https://')) {
            $website = 'https://' . ltrim($website, '/');
        }

        $licenseInfo['website'] = $website;

        // 選択されたライセンス情報をマージ
        if (!empty($selectedLicense['info'])) {
            $licenseInfo = array_merge($selectedLicense['info'], $licenseInfo);
        }

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
            $licenseInfo
        );

        // Optionally install and enable the plugin
        if ($this->confirm(__('command.make_plugin.confirm_install'), true)) {
            $this->installPlugin($pluginName, $pluginDirName);

            if ($this->confirm(__('command.make_plugin.confirm_enable'), true)) {
                $this->enablePlugin($pluginName);
            }
        }

        // composer.json に PSR-4 オートロード設定を更新
        $this->call('plugin:autoload:sync');

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
            "database/seeders/dev",
            "database/seeders/pro",

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
        array $licenseInfo
    ) {


        // 例: vendorName が null の場合や空文字の場合に 'plugins' をデフォルトとする
        $vendorNameDefault = $vendorName ?: 'plugins';

        // vendorName を StudlyCase に
        $vendorNameStudly = Str::studly($vendorNameDefault);

        // pluginName も StudlyCase に
        $pluginNameStudly = Str::studly($pluginName);

        // ライセンス情報を取得
        $licenseText = $licenseInfo['licenseText'] ?? '';
        $licenseName = $licenseInfo['licenseName'] ?? '';

        $placeholders = [
            '{{ pluginName }}'        => $pluginName,     // "MyPlugin"
            '{{ pluginNameStudly }}'  => $pluginNameStudly,
            '{{ pluginDirName }}'     => $pluginDirName,  // "MyPlugin"
            '{{ namespace }}'         => $namespace,
            '{{ pluginSlug }}'        => $pluginSlug,     // "my-plugin"
            '{{ license }}'           => $licenseText,
            '{{ licenseName }}'       => $licenseName,
            '{{ vendorName }}'        => $vendorName,     // 例: "plugins" or "exc-d"
            '{{ vendorNameDefault }}' => $vendorNameDefault,
            '{{ vendorNameStudly }}'  => $vendorNameStudly,
            '{{ softwareName }}'      => $softwareName,   // "MySoftware"
            '{{ cmsNameSlug }}'       => $cmsNameSlug,    // "my-software"
        ];


        // 初期ファイルを作成（js/css）
        File::put("{$pluginDir}/resources/src/js/app.js", "// JavaScript for {$pluginDirName}");
        File::put("{$pluginDir}/resources/src/css/style.scss", "/* SCSS for {$pluginDirName} */");


        // プラグインのメインファイルを作成
        
        // プラグイン専用の license-info.json を作成
        $this->createLicenseInfoFile($pluginName, $pluginDir, $licenseInfo);

        // ルートファイルを作成
        $this->createRoutes($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // 言語ファイルを作成
        $this->createLangFiles($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // コンフィグファイルを作成
        $this->createConfigFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // Vite 設定ファイルを作成
        $this->createViteConfigFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // composer.json を作成
        $this->createComposerFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // README.md を作成
        $this->createReadmeFile($pluginName, $pluginDir, $placeholders, $licenseInfo);

        // サービスプロバイダを生成
        $this->createServiceProvider($pluginName, $pluginDirName, $licenseInfo);

        
        // オプションに基づいて追加ファイルを作成
        if ($this->option('all')) {
            // オプションが指定されている場合は、全てのファイルを生成
            $this->createController($pluginName, $pluginDirName, $namespace);
            $this->createModel($pluginName, $pluginDirName, $namespace);
            $this->createMigration($pluginName, $pluginDirName);
            $this->createPolicy($pluginName, $pluginDirName, $namespace);
            $this->createListener($pluginName, $pluginDirName, $namespace);
            $this->createTests($pluginName, $pluginDirName);
            $this->createCommand($pluginName, $pluginDirName, $namespace);
            $this->createJob($pluginName, $pluginDirName, $namespace);
            $this->createNotification($pluginName, $pluginDirName, $namespace);
            $this->createResource($pluginName, $pluginDirName, $namespace);
            $this->createFactory($pluginName, $pluginDirName, $namespace);
            $this->createSeeder($pluginName, $pluginDirName);
        } else {
            // オプションに基づいて追加ファイルを作成
            if ($this->option('controller')) {
                $this->createController($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('model')) {
                $this->createModel($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('migration')) {
                $this->createMigration($pluginName, $pluginDirName);
            }

            if ($this->option('policy')) {
                $this->createPolicy($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('listener')) {
                $this->createListener($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('test')) {
                $this->createTests($pluginName, $pluginDirName);
            }

            if ($this->option('command')) {
                $this->createCommand($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('job')) {
                $this->createJob($pluginName, $pluginDirName, $namespace);
            }

            if ($this->option('notification')) {
                $this->createNotification($pluginName, $pluginDirName, $namespace);
            }
            if ($this->option('resource')) {
                $this->createResource($pluginName, $pluginDirName, $namespace);
            }
            if ($this->option('factory')) {
                $this->createFactory($pluginName, $pluginDirName, $namespace);
            }
            if ($this->option('seeder')) {
                $this->createSeeder($pluginName, $pluginDirName);
            }
        }
        
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
        $licenseName = $licenseInfo['license'] ?? 'GPL-3.0';
        
        // テンプレートがフルパスやテキストを含む場合、ファイル名のみを抽出
        $template = $licenseInfo['template'] ?? 'license-' . strtolower(str_replace([' ', '.'], ['-', ''], $licenseName)) . '.txt';
        if (str_contains($template, DIRECTORY_SEPARATOR)) {
            $template = basename($template);
        }
        
        // ライセンスデータを構築
        $licenseData = [
            'software' => $pluginName,
            'author' => $licenseInfo['author'] ?? 'My Company',
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
        // スタブファイルの内容を取得
        $stubContent = $this->getStubContent('routes.plugin.stub', $placeholders);

        // ファイルを生成
        $routesPath = "{$pluginDir}/routes/web.php";
        if (!file_exists(dirname($routesPath))) {
            mkdir(dirname($routesPath), 0755, true);
        }
        file_put_contents($routesPath, $stubContent);

        $this->info(__('command.make_plugin.files.routes'));
    }

    /**
     * コンフィグファイルを作成
     */
    protected function createConfigFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // コンフィグディレクトリを作成
        $configPath = "{$pluginDir}/config";
        if (!file_exists($configPath)) {
            mkdir($configPath, 0755, true);
        }

        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('config.stub', $placeholders);
        file_put_contents("{$configPath}/settings.php", $content);

        $this->info(__('command.make_plugin.files.config', ['pluginName' => $pluginName]));
    }

    /**
     * 言語ファイルを作成
     */
    protected function createLangFiles(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // 言語ファイルのディレクトリを作成
        $langDir = "{$pluginDir}/lang";
        foreach (['en', 'ja'] as $locale) {
            if (!file_exists("{$langDir}/{$locale}")) {
                mkdir("{$langDir}/{$locale}", 0755, true);
            }
        }

        // 英語の言語ファイル
        $contentEn = $this->getStubContent('messages.en.stub', $placeholders);
        file_put_contents("{$langDir}/en/messages.php", $contentEn);

        // 日本語の言語ファイル
        $contentJa = $this->getStubContent('messages.ja.stub', $placeholders);
        file_put_contents("{$langDir}/ja/messages.php", $contentJa);

        $this->info(__('command.make_plugin.files.lang', ['pluginName' => $pluginName]));
    }

    /**
     * Viteの設定ファイルを作成
     */
    protected function createViteConfigFile(string $pluginName, string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('vite.config.stub', $placeholders);
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

        $params = [
            'className' => $providerName,
            'pluginName' => $pluginName,
        ];
        
        Artisan::call('make:plugin:provider', $params);

        $this->info(__('command.make_plugin.files.service_provider', ['className' => $providerName, 'pluginName' => $pluginName]));
    }

    /**
     * コントローラを作成
     */
    protected function createController(string $pluginName, string $pluginDirName, string $namespace)
    {
        $controllerName = "{$pluginDirName}Controller";

        Artisan::call('make:plugin:controller', [
            'className' => $controllerName,
            'pluginName' => $pluginName,
            'scope' => 'plain',
            
        ]);

        $this->info(__('command.make_plugin.files.controller', ['className' => $controllerName, 'pluginName' => $pluginName]));
    }

    /**
     * モデルを作成
     */
    protected function createModel(string $pluginName, string $pluginDirName, string $namespace)
    {
        $modelName = "{$pluginDirName}";

        Artisan::call('make:plugin:model', [
            'pluginName' => $pluginName,
            'className' => $modelName,
        ]);

        $this->info(__('command.make_plugin.files.model', ['className' => $modelName, 'pluginName' => $pluginName]));
    }

    /**
     * ポリシーファイルを作成
     */
    protected function createPolicy(string $pluginName, string $pluginDirName, string $namespace)
    {
        $policyName = "{$pluginDirName}Policy";

        Artisan::call('make:plugin:policy', [
            'pluginName' => $pluginName,
            'className' => $policyName,
        ]);

        $this->info(__('command.make_plugin.files.policy', ['className' => $policyName, 'pluginName' => $pluginName]));
    }

    /**
     * イベントリスナーを作成
     */
    protected function createListener(string $pluginName, string $pluginDirName, string $namespace)
    {
        $listenerName = "{$pluginDirName}Listener";

        Artisan::call('make:plugin:listener', [
            'pluginName' => $pluginName,
            'className' => $listenerName,
        ]);

        $this->info(__('command.make_plugin.files.listener', ['className' => $listenerName, 'pluginName' => $pluginName]));
    }

    /**
     * テストファイルを作成
     */
    protected function createTests(string $pluginName, string $pluginDirName)
    {
        $testName = "{$pluginDirName}Test";

        Artisan::call('make:plugin:test', [
            'pluginName' => $pluginName,
            'className' => $testName,
        ]);

        $this->info(__('command.make_plugin.files.test', ['className' => $testName, 'pluginName' => $pluginName]));
    }


    /**
     * マイグレーションファイルを作成
     */
    protected function createMigration(string $pluginName, string $pluginDirName)
    {
        // プラグイン名をスネークケースに変換
        $pluginSnakeName = Str::snake($pluginDirName);
        // 日付を取得
        $date = now()->format('Y_m_d_His');
        // マイグレーション名を生成
        $migrationName = "{$date}_create_{$pluginSnakeName}_table";

        Artisan::call('make:plugin:migration', [
            'pluginName' => $pluginName,
            'className' => $migrationName,
        ]);

        $this->info(__('command.make_plugin.files.migration', ['className' => $migrationName, 'pluginName' => $pluginName]));
    }

    /**
     * リソースを作成
     */
    protected function createResource(string $pluginName, string $pluginDirName, string $namespace)
    {
        $resourceName = "{$pluginDirName}Resource";

        Artisan::call('make:plugin:resource', [
            'pluginName' => $pluginName,
            'className' => $resourceName,
        ]);

        $this->info(__('command.make_plugin.files.resource', ['className' => $resourceName, 'pluginName' => $pluginName]));
    }

    /**
     * コマンドを作成
     */
    protected function createCommand(string $pluginName, string $pluginDirName, string $namespace)
    {
        $commandName = "{$pluginDirName}Command";

        Artisan::call('pmake:plugin:command', [
            'pluginName' => $pluginName,
            'className' => $commandName,
        ]);

        $this->info(__('command.make_plugin.files.command', ['className' => $commandName, 'pluginName' => $pluginName]));
    }

    /**
     * ジョブを作成
     */
    protected function createJob(string $pluginName, string $pluginDirName, string $namespace)
    {
        $jobName = "{$pluginDirName}Job";

        Artisan::call('make:plugin:job', [
            'pluginName' => $pluginName,
            'className' => $jobName,
        ]);

        $this->info(__('command.make_plugin.files.job', ['className' => $jobName, 'pluginName' => $pluginName]));
    }

    /**
     * 通知を作成
     */
    protected function createNotification(string $pluginName, string $pluginDirName, string $namespace)
    {
        $notificationName = "{$pluginDirName}Notification";

        Artisan::call('make:plugin:notification', [
            'pluginName' => $pluginName,
            'className' => $notificationName,
        ]);

        $this->info(__('command.make_plugin.files.notification', ['className' => $notificationName, 'pluginName' => $pluginName]));
    }

    /**
     * シーダーを作成
     */
    protected function createSeeder(string $pluginName, string $pluginDirName)
    {
        // DatabaseSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'pluginName' => $pluginName,
            'className' => 'DatabaseSeeder',
        ]);


        // DevelopmentSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'pluginName' => $pluginName,
            'className' => 'DevelopmentSeeder',
            '--env' => 'dev',
            '--force' => true,
        ]);

        $this->info("Seeder DevelopmentSeeder.php created for plugin [{$pluginName}].");

        // ProductionSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'pluginName' => $pluginName,
            'className' => 'ProductionSeeder',
            '--env' => 'pro',
            '--force' => true,
        ]);

        $this->info("Seeder ProductionSeeder.php created for plugin [{$pluginName}].");
    }

    /**
     * ファクトリを作成
     */
    protected function createFactory(string $pluginName, string $pluginDirName, string $namespace)
    {
        $factoryName = "{$pluginDirName}Factory";

        Artisan::call('make:plugin:factory', [
            'pluginName' => $pluginName,
            'className' => $factoryName,
        ]);

        $this->info(__('command.make_plugin.files.factory', ['className' => $factoryName, 'pluginName' => $pluginName]));
    }

    

    
    /**
     * プラグインをインストール (DBに登録）
     */
    protected function installPlugin($pluginName, $pluginDirName)
    {
        // Convert plugin name to kebab-case for slug (e.g., 'MyPlugin' → 'my-plugin')
        $slug = Str::slug(Str::headline($pluginName), '-');
            
        // Register plugin in database
        Plugin::create([
            'name' => $pluginName,
            'slug' => $slug,
            'directory' => $pluginDirName,
            'namespace' => "Plugins\\$pluginDirName",
            'version' => '1.0.0',
            'status' => 0,
        ]);

        // Run migrations if exist
        $pluginMigrationPath = base_path("plugins/{$pluginDirName}/migrations");
        if (is_dir($pluginMigrationPath)) {
            Artisan::call('migrate', [
                '--path' => "plugins/{$pluginDirName}/migrations",
                '--force' => true,
            ]);
        }

        $this->info(__('command.make_plugin.installed', ['pluginName' => $pluginName]));
    }

    /**
     * プラグインを有効化 (DBでstatusを1に変更)
     */

    protected function enablePlugin($pluginName)
    {
        // Enable the plugin
        $plugin = Plugin::where('name', $pluginName)->first();
        if ($plugin) {
            $plugin->update(['status' => 1]);
            $this->createPluginSymlink($plugin->directory);
            $this->info(__('command.make_plugin.enabled', ['pluginName' => $pluginName]));
        } else {
            $this->error(__('command.make_plugin.not_found', ['pluginName' => $pluginName]));
        }
    }


    /**
     * public配下へアセットディレクトリのシンボリックリンクを作成
     */
    protected function createPluginSymlink(string $pluginDirName)
    {
        $target = base_path("plugins/{$pluginDirName}/resources/assets");
        $link = public_path("assets/plugins/{$pluginDirName}");

        if (File::exists($target) && is_dir($target)) {
            if (File::exists($link) || is_link($link)) {
                unlink($link);
            }
            symlink($target, $link);
        } else {
            $this->warn(__('command.make_plugin.no_assets', ['name' => $pluginDirName]));
        }
    }
}
