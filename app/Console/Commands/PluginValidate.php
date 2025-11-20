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

class PluginValidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:validate 
                            {plugin : The plugin name to validate}
                            {--strict : Enable strict validation mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the structure and configuration of a plugin';

    /**
     * Validation results
     */
    protected array $errors = [];
    protected array $warnings = [];
    protected array $passed = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $pluginPath = base_path("plugins/{$pluginName}");
        $strict = $this->option('strict');

        $this->info("🔍 Validating plugin: {$pluginName}");
        $this->newLine();

        // プラグインディレクトリの存在確認
        if (!File::isDirectory($pluginPath)) {
            $this->error("❌ Plugin directory not found: {$pluginPath}");
            return 1;
        }

        // 各種検証を実行
        $this->validateDirectoryStructure($pluginPath, $pluginName);
        $this->validateComposerJson($pluginPath, $pluginName);
        $this->validateServiceProvider($pluginPath, $pluginName);
        $this->validateLicenseInfo($pluginPath, $pluginName);
        $this->validateRoutes($pluginPath, $pluginName);
        $this->validateMigrations($pluginPath, $pluginName);
        
        if ($strict) {
            $this->validateStrictMode($pluginPath, $pluginName);
        }

        // 結果を表示
        $this->displayResults($pluginName);

        // エラーがある場合は終了コード1を返す
        return count($this->errors) > 0 ? 1 : 0;
    }

    /**
     * ディレクトリ構造の検証
     */
    protected function validateDirectoryStructure($pluginPath, $pluginName)
    {
        $this->info('📁 Checking directory structure...');

        $requiredDirs = [
            'app',
            'config',
            'database',
            'resources',
            'routes',
        ];

        $optionalDirs = [
            'app/Http/Controllers',
            'app/Models',
            'app/Providers',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'resources/views',
            'resources/lang',
            'public',
            'tests',
        ];

        foreach ($requiredDirs as $dir) {
            $dirPath = "{$pluginPath}/{$dir}";
            if (File::isDirectory($dirPath)) {
                $this->passed[] = "✓ Required directory exists: {$dir}";
            } else {
                $this->errors[] = "✗ Required directory missing: {$dir}";
            }
        }

        foreach ($optionalDirs as $dir) {
            $dirPath = "{$pluginPath}/{$dir}";
            if (!File::isDirectory($dirPath)) {
                $this->warnings[] = "⚠ Optional directory missing: {$dir}";
            }
        }
    }

    /**
     * composer.jsonの検証
     */
    protected function validateComposerJson($pluginPath, $pluginName)
    {
        $this->info('📦 Checking composer.json...');

        $composerPath = "{$pluginPath}/composer.json";

        if (!File::exists($composerPath)) {
            $this->errors[] = "✗ composer.json not found";
            return;
        }

        $this->passed[] = "✓ composer.json exists";

        $composer = json_decode(File::get($composerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = "✗ composer.json is not valid JSON: " . json_last_error_msg();
            return;
        }

        // 必須フィールドの確認
        $requiredFields = ['name', 'description', 'autoload'];
        foreach ($requiredFields as $field) {
            if (!isset($composer[$field])) {
                $this->errors[] = "✗ composer.json missing required field: {$field}";
            } else {
                $this->passed[] = "✓ composer.json has field: {$field}";
            }
        }

        // typeの確認（推奨）
        if (!isset($composer['type'])) {
            $this->warnings[] = "⚠ composer.json missing optional field 'type' (recommended: 'library' or 'dixlase-plugin')";
        } elseif ($composer['type'] !== 'library' && $composer['type'] !== 'dixlase-plugin') {
            $this->warnings[] = "⚠ composer.json type is '{$composer['type']}' (recommended: 'library' or 'dixlase-plugin')";
        } else {
            $this->passed[] = "✓ composer.json has valid type: {$composer['type']}";
        }

        // autoloadの確認
        if (isset($composer['autoload']['psr-4'])) {
            $expectedNamespace = "Plugins\\{$pluginName}\\";
            if (!isset($composer['autoload']['psr-4'][$expectedNamespace])) {
                $this->warnings[] = "⚠ PSR-4 autoload should include: {$expectedNamespace}";
            } else {
                $this->passed[] = "✓ PSR-4 autoload configured correctly";
            }
        }
    }

    /**
     * ServiceProviderの検証
     */
    protected function validateServiceProvider($pluginPath, $pluginName)
    {
        $this->info('🔌 Checking ServiceProvider...');

        $providerPath = "{$pluginPath}/app/Providers/{$pluginName}ServiceProvider.php";

        if (!File::exists($providerPath)) {
            $this->errors[] = "✗ ServiceProvider not found: {$pluginName}ServiceProvider.php";
            return;
        }

        $this->passed[] = "✓ ServiceProvider exists";

        $content = File::get($providerPath);

        // 必須メソッドの確認
        $requiredMethods = ['register', 'boot'];
        foreach ($requiredMethods as $method) {
            if (strpos($content, "public function {$method}()") !== false) {
                $this->passed[] = "✓ ServiceProvider has {$method}() method";
            } else {
                $this->warnings[] = "⚠ ServiceProvider missing {$method}() method";
            }
        }

        // 名前空間の確認
        $expectedNamespace = "namespace Plugins\\{$pluginName}\\App\\Providers;";
        if (strpos($content, $expectedNamespace) !== false) {
            $this->passed[] = "✓ ServiceProvider namespace is correct";
        } else {
            $this->errors[] = "✗ ServiceProvider namespace is incorrect";
        }
    }

    /**
     * ライセンス情報の検証
     */
    protected function validateLicenseInfo($pluginPath, $pluginName)
    {
        $this->info('📄 Checking license information...');

        $licenseJsonPath = "{$pluginPath}/license-info.json";

        if (!File::exists($licenseJsonPath)) {
            $this->warnings[] = "⚠ license-info.json not found";
            return;
        }

        $this->passed[] = "✓ license-info.json exists";

        $licenseInfo = json_decode(File::get($licenseJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = "✗ license-info.json is not valid JSON: " . json_last_error_msg();
            return;
        }

        // 必須フィールドの確認
        $requiredFields = ['license_type', 'copyright_holder', 'copyright_year'];
        foreach ($requiredFields as $field) {
            if (!isset($licenseInfo[$field])) {
                $this->warnings[] = "⚠ license-info.json missing field: {$field}";
            } else {
                $this->passed[] = "✓ license-info.json has field: {$field}";
            }
        }
    }

    /**
     * ルートファイルの検証
     */
    protected function validateRoutes($pluginPath, $pluginName)
    {
        $this->info('🛣️  Checking routes...');

        $routesPath = "{$pluginPath}/routes";

        if (!File::isDirectory($routesPath)) {
            $this->warnings[] = "⚠ routes directory not found";
            return;
        }

        $routeFiles = ['web.php', 'api.php'];
        $foundRoutes = false;

        foreach ($routeFiles as $file) {
            $filePath = "{$routesPath}/{$file}";
            if (File::exists($filePath)) {
                $this->passed[] = "✓ Route file exists: {$file}";
                $foundRoutes = true;
            }
        }

        if (!$foundRoutes) {
            $this->warnings[] = "⚠ No route files found (web.php or api.php)";
        }
    }

    /**
     * マイグレーションの検証
     */
    protected function validateMigrations($pluginPath, $pluginName)
    {
        $this->info('🗄️  Checking migrations...');

        $migrationsPath = "{$pluginPath}/database/migrations";

        if (!File::isDirectory($migrationsPath)) {
            $this->warnings[] = "⚠ migrations directory not found";
            return;
        }

        $migrations = File::files($migrationsPath);

        if (count($migrations) === 0) {
            $this->warnings[] = "⚠ No migration files found";
        } else {
            $this->passed[] = "✓ Found " . count($migrations) . " migration file(s)";

            // マイグレーションファイル名の形式確認
            foreach ($migrations as $migration) {
                $filename = $migration->getFilename();
                if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $filename)) {
                    $this->warnings[] = "⚠ Migration file has invalid name format: {$filename}";
                }
            }
        }
    }

    /**
     * 厳格モードの検証
     */
    protected function validateStrictMode($pluginPath, $pluginName)
    {
        $this->info('🔒 Running strict validation...');

        // README.mdの確認
        if (!File::exists("{$pluginPath}/README.md")) {
            $this->warnings[] = "⚠ README.md not found";
        } else {
            $this->passed[] = "✓ README.md exists";
        }

        // LICENSEファイルの確認
        if (!File::exists("{$pluginPath}/LICENSE")) {
            $this->warnings[] = "⚠ LICENSE file not found";
        } else {
            $this->passed[] = "✓ LICENSE file exists";
        }

        // テストディレクトリの確認
        if (!File::isDirectory("{$pluginPath}/tests")) {
            $this->warnings[] = "⚠ tests directory not found";
        } else {
            $testFiles = File::allFiles("{$pluginPath}/tests");
            if (count($testFiles) === 0) {
                $this->warnings[] = "⚠ No test files found";
            } else {
                $this->passed[] = "✓ Found " . count($testFiles) . " test file(s)";
            }
        }

        // .gitignoreの確認
        if (!File::exists("{$pluginPath}/.gitignore")) {
            $this->warnings[] = "⚠ .gitignore not found";
        } else {
            $this->passed[] = "✓ .gitignore exists";
        }
    }

    /**
     * 検証結果の表示
     */
    protected function displayResults($pluginName)
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 Validation Results for: {$pluginName}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // エラー表示
        if (count($this->errors) > 0) {
            $this->error('❌ Errors (' . count($this->errors) . '):');
            foreach ($this->errors as $error) {
                $this->line("   {$error}");
            }
            $this->newLine();
        }

        // 警告表示
        if (count($this->warnings) > 0) {
            $this->warn('⚠️  Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line("   {$warning}");
            }
            $this->newLine();
        }

        // 成功表示
        if (count($this->passed) > 0) {
            $this->info('✅ Passed (' . count($this->passed) . '):');
            foreach ($this->passed as $pass) {
                $this->line("   {$pass}");
            }
            $this->newLine();
        }

        // サマリー
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $total = count($this->errors) + count($this->warnings) + count($this->passed);
        $this->info("Total checks: {$total}");
        $this->line("  ✅ Passed: " . count($this->passed));
        $this->line("  ⚠️  Warnings: " . count($this->warnings));
        $this->line("  ❌ Errors: " . count($this->errors));
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if (count($this->errors) === 0) {
            $this->info('🎉 Plugin validation passed!');
        } else {
            $this->error('💥 Plugin validation failed. Please fix the errors above.');
        }
    }
}
