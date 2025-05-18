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

        // ライセンス情報を取得（プラグイン用なので警告メッセージは表示しない）
        $selectedLicense = $this->getNewLicenseInfo(false);

        // 開発者情報の取得
        $author = $this->ask(__('command.make_plugin.enter_author_name'));

        // URL入力を補完（https://を自動追加）
        $website = $this->ask(__('command.make_plugin.enter_website_url'));
        if (!Str::startsWith($website, 'https://')) {
            $website = 'https://' . ltrim($website, '/');
        }

        // ライセンス情報を構築
        $licenseInfo = [
            'software' => $pluginName,
            'author' => $author,
            'website' => $website,
            'license' => $selectedLicense['name'] ?? '',
            'template' => $selectedLicense['template'] ?? '',
            'text' => $selectedLicense['text'] ?? '',
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
            [
                'license' => $selectedLicense['name'] ?? '',
                'licenseName' => $selectedLicense['name'] ?? '',
                'licenseText' => $selectedLicense['text'] ?? '',
                'text' => $selectedLicense['text'] ?? '', // 後方互換性のため
                'template' => $selectedLicense['template'] ?? '',
                'author' => $author,
                'website' => $website,
                'software' => $pluginName
            ]
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

        $this->info(__('command.make_plugin.success', ['name' => $pluginName]));
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


        $this->info(__('command.make_plugin.files.service_provider', ['name' => $providerName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.controller', ['name' => $controllerName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.model', ['name' => $modelName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.policy', ['name' => $policyName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.listener', ['name' => $listenerName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.test', ['name' => $testName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.migration', ['name' => $migrationName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.resource', ['name' => $resourceName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.command', ['name' => $commandName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.job', ['name' => $jobName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.notification', ['name' => $notificationName, 'plugin' => $pluginName]));
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

        $this->info(__('command.make_plugin.files.factory', ['name' => $factoryName, 'plugin' => $pluginName]));
    }

    /**
     * ルートファイルを作成
     */
    protected function createRoutes(string $pluginDir, array $placeholders, array $licenseInfo)
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
    protected function createConfigFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // コンフィグディレクトリを作成
        $configPath = "{$pluginDir}/config";
        if (!file_exists($configPath)) {
            mkdir($configPath, 0755, true);
        }

        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('config.stub', $placeholders);
        file_put_contents("{$configPath}/settings.php", $content);

        $this->info(__('command.make_plugin.files.config'));
    }

    /**
     * 言語ファイルを作成
     */
    protected function createLangFiles(string $pluginDir, array $placeholders, array $licenseInfo)
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

        $this->info(__('command.make_plugin.files.lang'));
    }

    /**
     * Viteの設定ファイルを作成
     */
    protected function createViteConfigFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('vite.config.stub', $placeholders);
        file_put_contents("{$pluginDir}/vite.config.js", $content);

        $this->info(__('command.make_plugin.files.vite'));
    }

    /**
     * composer.json を作成
     */
    protected function createComposerFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('composer.plugin.stub', $placeholders);
        file_put_contents("{$pluginDir}/composer.json", $content);

        $this->info(__('command.make_plugin.files.composer'));
    }

    /**
     * README.md を作成
     */
    protected function createReadmeFile(string $pluginDir, array $placeholders, array $licenseInfo)
    {
        // スタブファイルの内容を取得してファイルを生成
        $content = $this->getStubContent('readme.plugin.stub', $placeholders);
        file_put_contents("{$pluginDir}/README.md", $content);

        $this->info(__('command.make_plugin.files.readme'));
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

        $this->info(__('command.make_plugin.installed', ['name' => $pluginName]));
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
            $this->info(__('command.make_plugin.enabled', ['name' => $pluginName]));
        } else {
            $this->error(__('command.make_plugin.not_found', ['name' => $pluginName]));
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
