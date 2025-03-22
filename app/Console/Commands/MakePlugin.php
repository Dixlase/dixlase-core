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
use App\Services\FileGenerator;

class MakePlugin extends Command
{
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

    protected FileGenerator $fileGenerator;


    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {

        // ソフトウェア名
        $softwareName = config('app.name');
        // ソフトウェア名をスネークケースに変換
        $cmsNameSlug = Str::slug(Str::snake($softwareName));
        // プラグイン名
        $pluginName = $this->ask('プラグイン名を入力してください');
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


        // ライセンスの選択
        $licenseOption = $this->choice(
            'ライセンスを選択してください',
            [
                'gpl' => 'GPL-3.0',
                'agpl' => 'AGPL-3.0',
                'mit' => 'MIT',
                'apache' => 'Apache-2.0',
                'bsd3' => 'BSD-3-Clause',
                'lgpl' => 'LGPL-3.0',
                'commercial' => 'Commercial',
                'custom' => '独自ライセンス'
            ],
            'gpl'
        );

        $licenseMap = [
            'gpl' => ['name' => 'GPL-3.0', 'file' => 'license-gpl.txt'],
            'agpl' => ['name' => 'AGPL-3.0', 'file' => 'license-agpl.txt'],
            'mit' => ['name' => 'MIT', 'file' => 'license-mit.txt'],
            'apache' => ['name' => 'Apache-2.0', 'file' => 'license-apache.txt'],
            'bsd3' => ['name' => 'BSD-3-Clause', 'file' => 'license-bsd3.txt'],
            'lgpl' => ['name' => 'LGPL-3.0', 'file' => 'license-lgpl.txt'],
            'commercial' => ['name' => 'Commercial', 'file' => 'license-commercial.txt'],
            'custom' => ['name' => 'Custom License', 'file' => 'license-custom.txt'],
        ];

        if (File::exists($pluginDir)) {
            $this->error("The plugin '{$pluginName}' already exists.");
            return Command::FAILURE;
        }

        $licenseType = $licenseMap[$licenseOption]['name'];
        $licenseTemplate = "license-templates/{$licenseMap[$licenseOption]['file']}";


        // 開発者情報の取得
        $author = $this->ask('開発者名を入力してください');

        // URL入力を補完（https://を自動追加）
        $website = $this->ask('開発者のWebサイトURLを入力してください（https://の後の部分のみ入力）');
        if (!Str::startsWith($website, 'https://')) {
            $website = 'https://' . ltrim($website, '/');
        }



        if (File::exists($pluginDir)) {
            $this->error("The plugin '{$pluginName}' already exists.");
            return Command::FAILURE;
        }

        // ライセンス情報を構築
        $licenseInfo = [
            'software' => $pluginName,
            'author' => $author,
            'website' => $website,
            'license' => $licenseType,
            'template' => $licenseTemplate,
        ];

        // プラグインディレクトリを作成
        $this->createPluginDirectories($pluginDir, $pluginName, $namespace, $pluginDirName);

        // プラグイン専用の license-info.json を作成
        File::put("{$pluginDir}/license-info.json", json_encode($licenseInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
        if ($this->confirm('プラグインをインストールしますか？', true)) {
            $this->installPlugin($pluginName, $pluginDirName);

            if ($this->confirm('プラグインを有効化しますか？', true)) {
                $this->enablePlugin($pluginName);
            }
        }

        // composer.json に PSR-4 オートロード設定を更新
        $this->call('plugin:autoload:sync');

        $this->info("Plugin {$pluginName} has been created successfully!");
        return Command::SUCCESS;
    }

    protected function getLicenseInfo(): array
    {
        $defaultLicenseInfo = [
            'software' => 'MySoftware',
            'author' => 'Your Name',
            'website' => 'https://example.com',
            'license' => 'GPL-3.0',
            'template' => 'license-templates/license-agpl.txt',
        ];

        $licenseInfoPath = base_path('license-info.json');

        if (File::exists($licenseInfoPath)) {
            $licenseData = json_decode(File::get($licenseInfoPath), true);
            return array_merge($defaultLicenseInfo, $licenseData);
        }

        return $defaultLicenseInfo;
    }

    protected function createPluginDirectories(string $pluginDir, string $pluginName, string $namespace, string $pluginDirName)
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

        // ライセンスの取得（必要な場合）
        $licenseName = $this->fileGenerator->getLicenseName();

        // ライセンス情報を取得
        $licenseText = $this->fileGenerator->getLicenseForPhp($licenseInfo);

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
        // ルートファイルを作成
        $this->createRoutes($pluginDir, $placeholders, $licenseInfo);

        // 言語ファイルを作成
        $this->createLangFiles($pluginDir, $placeholders, $licenseInfo);

        // コンフィグファイルを作成
        $this->createConfigFile($pluginDir, $placeholders, $licenseInfo);

        // Vite 設定ファイルを作成
        $this->createViteConfigFile($pluginDir, $placeholders, $licenseInfo);

        // composer.json を作成
        $this->createComposerFile($pluginDir, $placeholders, $licenseInfo);

        // README.md を作成
        $this->createReadmeFile($pluginDir, $placeholders, $licenseInfo);

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
     * サービスプロバイダを作成
     */
    protected function createServiceProvider(string $pluginName, string $pluginDirName, array $licenseInfo)
    {
        $providerName = "{$pluginDirName}ServiceProvider";

        Artisan::call('make:plugin:provider', [
            'plugin' => $pluginDirName,
            'name' => $providerName,
            '--plugin' => true,
            '--force' => true,
            '--license-info' => json_encode($licenseInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);


        $this->info("Service provider [{$providerName}] created for plugin [{$pluginName}].");
    }

    /**
     * コントローラを作成
     */
    protected function createController(string $pluginName, string $pluginDirName, string $namespace)
    {
        $controllerName = "{$pluginDirName}Controller";

        Artisan::call('make:plugin:controller', [
            'plugin' => $pluginDirName,
            'name' => $controllerName,
        ]);

        $this->info("Controller [{$controllerName}] created for plugin [{$pluginName}].");
    }

    /**
     * モデルを作成
     */
    protected function createModel(string $pluginName, string $pluginDirName, string $namespace)
    {
        $modelName = "{$pluginDirName}";

        Artisan::call('make:plugin:model', [
            'plugin' => $pluginDirName,
            'name' => $modelName,
        ]);

        $this->info("Model [{$modelName}] created for plugin [{$pluginName}].");
    }

    /**
     * ポリシーファイルを作成
     */
    protected function createPolicy(string $pluginName, string $pluginDirName, string $namespace)
    {
        $policyName = "{$pluginDirName}Policy";

        Artisan::call('make:plugin:policy', [
            'plugin' => $pluginDirName,
            'name' => $policyName,
        ]);

        $this->info("Policy [{$policyName}] created for plugin [{$pluginName}].");
    }

    /**
     * イベントリスナーを作成
     */
    protected function createListener(string $pluginName, string $pluginDirName, string $namespace)
    {
        $listenerName = "{$pluginDirName}Listener";

        Artisan::call('make:plugin:listener', [
            'plugin' => $pluginDirName,
            'name' => $listenerName,
        ]);

        $this->info("Listener [{$listenerName}] created for plugin [{$pluginName}].");
    }

    /**
     * テストファイルを作成
     */
    protected function createTests(string $pluginName, string $pluginDirName)
    {
        $testName = "{$pluginDirName}Test";

        Artisan::call('make:plugin:test', [
            'plugin' => $pluginDirName,
            'name' => $testName,
        ]);

        $this->info("Test [{$testName}] created for plugin [{$pluginName}].");
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
            'plugin' => $pluginDirName,
            'name' => $migrationName,
        ]);

        $this->info("Migration {$migrationName} created for plugin [{$pluginName}].");
    }

    /**
     * リソースを作成
     */
    protected function createResource(string $pluginName, string $pluginDirName, string $namespace)
    {
        $resourceName = "{$pluginDirName}Resource";

        Artisan::call('make:plugin:resource', [
            'plugin' => $pluginDirName,
            'name' => $resourceName,
        ]);

        $this->info("Resource [{$resourceName}] created for plugin [{$pluginName}].");
    }

    /**
     * コマンドを作成
     */
    protected function createCommand(string $pluginName, string $pluginDirName, string $namespace)
    {
        $commandName = "{$pluginDirName}Command";

        Artisan::call('pmake:plugin:command', [
            'plugin' => $pluginDirName,
            'name' => $commandName,
        ]);

        $this->info("Command [{$commandName}] created for plugin [{$pluginName}].");
    }

    /**
     * ジョブを作成
     */
    protected function createJob(string $pluginName, string $pluginDirName, string $namespace)
    {
        $jobName = "{$pluginDirName}Job";

        Artisan::call('make:plugin:job', [
            'plugin' => $pluginDirName,
            'name' => $jobName,
        ]);

        $this->info("Job [{$jobName}] created for plugin [{$pluginName}].");
    }

    /**
     * 通知を作成
     */
    protected function createNotification(string $pluginName, string $pluginDirName, string $namespace)
    {
        $notificationName = "{$pluginDirName}Notification";

        Artisan::call('make:plugin:notification', [
            'plugin' => $pluginDirName,
            'name' => $notificationName,
        ]);

        $this->info("Notification [{$notificationName}] created for plugin [{$pluginName}].");
    }

    /**
     * シーダーを作成
     */
    protected function createSeeder(string $pluginName, string $pluginDirName)
    {
        // DatabaseSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'plugin' => $pluginDirName,
            'name' => 'DatabaseSeeder',
            '--force' => true,
        ]);
        $this->info("Seeder DatabaseSeeder.php created for plugin [{$pluginName}].");

        // DevelopmentSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'plugin' => $pluginDirName,
            'name' => 'DevelopmentSeeder',
            '--env' => 'dev',
            '--force' => true,
        ]);
        $this->info("Seeder DevelopmentSeeder.php created for plugin [{$pluginName}].");

        // ProductionSeeder の作成
        Artisan::call('make:plugin:seeder', [
            'plugin' => $pluginDirName,
            'name' => 'ProductionSeeder',
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
            'plugin' => $pluginDirName,
            'name' => $factoryName,
        ]);

        $this->info("Factory [{$factoryName}] created for plugin [{$pluginName}].");
    }

    /**
     * ルートファイルを作成
     */
    protected function createRoutes(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        $stubFile = $this->fileGenerator->getStubContent(
            'routes.plugin.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/routes/web.php", $fileContent);

        $this->info("Routes file created for plugin.");
    }

    /**
     * コンフィグファイルを作成
     */
    protected function createConfigFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        $stubFile = $this->fileGenerator->getStubContent(
            'config.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/config/settings.php", $fileContent);

        $this->info("Config file created for plugin.");
    }

    /**
     * 言語ファイルを作成
     */
    protected function createLangFiles(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        // 英語の言語ファイル
        $stubFileEn = $this->fileGenerator->getStubContent(
            'messages.en.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContentEn = $this->fileGenerator->replacePlaceholders($stubFileEn, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/lang/en/messages.php", $fileContentEn);

        // 日本語の言語ファイル
        $stubFileJa = $this->fileGenerator->getStubContent(
            'messages.ja.stub',
            null,
            $stubPath,
            $licenseInfo
        );
        $fileContentJa = $this->fileGenerator->replacePlaceholders($stubFileJa, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/lang/ja/messages.php", $fileContentJa);

        $this->info("Language files (en & ja) created for plugin.");
    }

    /**
     * Vite 設定ファイルを作成
     */
    protected function createViteConfigFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        $stubFile = $this->fileGenerator->getStubContent(
            'vite.config.plugin.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/vite.config.js", $fileContent);

        $this->info("Vite config file created for plugin.");
    }

    /**
     * composer.json を作成
     */
    protected function createComposerFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        $stubFile = $this->fileGenerator->getStubContent(
            'composer.plugin.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/composer.json", $fileContent);

        $this->info("Composer.json file created for plugin.");
    }

    /**
     * README.md を作成
     */
    protected function createReadmeFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        $stubPath = [base_path('stubs/custom')];

        $stubFile = $this->fileGenerator->getStubContent(
            'README.plugin.stub',
            null,
            $stubPath,
            $licenseInfo
        );

        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/README.md", $fileContent);

        $this->info("README.md created for plugin.");
    }



    /**
     * プラグインを有効化 (DBに登録）
     */
    protected function installPlugin($pluginName, $pluginDirName)
    {
        // Register plugin in database
        Plugin::create([
            'name' => $pluginName,
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

        $this->info("Plugin {$pluginName} has been installed.");
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
            $this->info("Plugin '{$pluginName}' has been enabled.");
        } else {
            $this->error("Plugin '{$pluginName}' not found in the database.");
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
            $this->warn("No assets directory found for plugin '{$pluginDirName}'.");
        }
    }
}
