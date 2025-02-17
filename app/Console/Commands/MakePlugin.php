<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

    protected $signature = 'make:plugin {name}
                            {--namespace=Vendor}
                            {--vendor=plugins}
                            {--install}
                            {--enable}
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
        // 1) license-info.txt を探す
        $licenseInfoPath = base_path('license-info.json');

        // 2) デフォルトのソフトウェア名（万が一ファイルがない場合など）
        $defaultSoftwareName = 'MySoftware';

        if (File::exists($licenseInfoPath)) {
            // license-info.txt が存在するなら読み込む
            $licenseInfoJson = File::get($licenseInfoPath);
            $licenseInfo = json_decode($licenseInfoJson, true);
            $softwareName = $licenseInfo['software'] ?? $defaultSoftwareName;
        } else {
            // 存在しなければデフォルト値を採用
            $softwareName = $defaultSoftwareName;
        }

        // 例: "MySoftware" -> "my-software"
        $cmsNameSlug = Str::slug(Str::snake($softwareName));

        // 人間が認識する名前
        $pluginName = $this->argument('name');

        // スラッグ化したプラグイン名
        $pluginSlug = Str::slug(Str::snake($pluginName));

        // キャメルケースでディレクトリ名を生成
        $pluginDirName = Str::studly($pluginName);

        // ベンダー名 (Composerパッケージ用)
        $vendorName = $this->option('vendor') ?: 'plugins';

        // このように "my-software-pages-plugin" を組み立てる
        $packageName = "{$cmsNameSlug}-{$pluginSlug}";

        // プラグインの名前空間
        $namespace = $this->option('namespace') . '\\' . $pluginDirName;

        // プラグイン保存先のパス
        $pluginDir = base_path("plugins/{$pluginDirName}");

        if (File::exists($pluginDir)) {
            $this->error("The plugin '{$pluginName}' already exists.");
            return Command::FAILURE;
        }

        // Create directories and default files
        $this->createPluginDirectories($pluginDir, $pluginName, $namespace, $pluginDirName);


        // スタブファイルを使って各種ファイルを生成
        $this->createPluginFiles(
            $pluginDir,
            $pluginName,
            $pluginDirName,
            $vendorName,
            $namespace,
            $cmsNameSlug,
            $pluginSlug,
            $softwareName
        );

        // Optionally install and enable the plugin
        if ($this->option('install')) {
            $this->installPlugin($pluginName, $pluginDirName);

            if ($this->option('enable')) {
                $this->enablePlugin($pluginName);
            }
        }

        // composer.json に PSR-4 オートロード設定を更新
        $this->call('plugin:autoload:sync');

        $this->info("Plugin {$pluginName} has been created successfully!");
        return Command::SUCCESS;
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
        string $softwareName
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
        $licenseText = $this->fileGenerator->getLicenseContent();

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


        // 1) プラグインのメインファイルを作成
        //スタブファイルのパス
        $stubPath = [base_path('stubs/custom')];

        // routes/web.php
        $stubFile = $this->fileGenerator->getStubContent(
            'routes.plugin.stub',
            null,
            $stubPath
        );
        // スタブ内容にプレースホルダを適用
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/routes/web.php", $fileContent);

        // lang/en/messages.php
        $stubFile = $this->fileGenerator->getStubContent(
            'messages.en.stub',
            null,
            $stubPath
        );

        // 言語ファイルを作成
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/lang/en/messages.php", $fileContent);

        // lang/ja/messages.php
        $stubFile = $this->fileGenerator->getStubContent(
            'messages.ja.stub',
            null,
            $stubPath
        );
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/lang/ja/messages.php", $fileContent);

        // config file
        $stubFile = $this->fileGenerator->getStubContent(
            'config.stub',
            null,
            $stubPath
        );

        // 設定ファイルを作成
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        // プラグイン名をスネークケースに変換
        $snakeName = Str::snake($pluginName);
        $this->fileGenerator->generateFile("{$pluginDir}/config/settings.php", $fileContent);

        // vite.config.js
        $stubFile = $this->fileGenerator->getStubContent(
            'vite.config.plugin.stub',
            null,
            $stubPath
        );
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/vite.config.js", $fileContent);

        // composer.json
        $stubFile = $this->fileGenerator->getStubContent(
            'composer.plugin.stub',
            null,
            $stubPath
        );
        $fileContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/composer.json", $fileContent);

        // 7) README.md
        $stubFile    = $this->fileGenerator->getStubContent('README.plugin.stub', null, $stubPath);
        $readmeContent = $this->fileGenerator->replacePlaceholders($stubFile, $placeholders);
        $this->fileGenerator->generateFile("{$pluginDir}/README.md", $readmeContent);

        // サービスプロバイダを生成
        $this->createServiceProvider($pluginName, $pluginDirName);

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
    protected function createServiceProvider(string $pluginName, string $pluginDirName)
    {
        $providerName = "{$pluginDirName}ServiceProvider";

        Artisan::call('make:plugin:provider', [
            'plugin' => $pluginDirName,
            'name' => $providerName,
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
