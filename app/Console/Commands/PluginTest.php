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
use Symfony\Component\Process\Process;

class PluginTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:test 
                            {plugin : The plugin name to test}
                            {--pest : Run tests using Pest}
                            {--phpunit : Run tests using PHPUnit (default)}
                            {--filter= : Filter tests by name}
                            {--group= : Run tests in a specific group}
                            {--coverage : Generate code coverage report}
                            {--parallel : Run tests in parallel}
                            {--stop-on-failure : Stop on first failure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tests for a specific plugin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $pluginPath = base_path("plugins/{$pluginName}");

        // プラグインディレクトリの存在確認
        if (!File::isDirectory($pluginPath)) {
            $this->error("❌ Plugin directory not found: {$pluginPath}");
            return 1;
        }

        // テストディレクトリの確認
        $testsPath = "{$pluginPath}/tests";
        if (!File::isDirectory($testsPath)) {
            $this->error("❌ Tests directory not found: {$testsPath}");
            $this->line("💡 Hint: Create tests using 'php artisan make:plugin:test'");
            return 1;
        }

        // テストファイルの確認
        $testFiles = File::allFiles($testsPath);
        if (count($testFiles) === 0) {
            $this->warn("⚠️  No test files found in: {$testsPath}");
            return 1;
        }

        $this->info("🧪 Running tests for plugin: {$pluginName}");
        $this->line("📁 Tests directory: {$testsPath}");
        $this->line("📝 Test files: " . count($testFiles));
        $this->newLine();

        // テストランナーの決定
        $usePest = $this->option('pest') || $this->isPestConfigured($pluginPath);
        $runner = $usePest ? 'pest' : 'phpunit';

        // テストコマンドの構築
        $command = $this->buildTestCommand($pluginPath, $runner);

        // テストの実行
        return $this->runTests($command, $pluginName, $runner);
    }

    /**
     * Pestが設定されているか確認
     */
    protected function isPestConfigured($pluginPath)
    {
        // Pest.php の存在確認
        if (File::exists("{$pluginPath}/tests/Pest.php")) {
            return true;
        }

        // phpunit.xmlでPestが設定されているか確認
        $phpunitXml = "{$pluginPath}/phpunit.xml";
        if (File::exists($phpunitXml)) {
            $content = File::get($phpunitXml);
            if (Str::contains($content, 'Pest\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * テストコマンドを構築
     */
    protected function buildTestCommand($pluginPath, $runner)
    {
        $baseDir = base_path();
        
        if ($runner === 'pest') {
            $command = [
                "{$baseDir}/vendor/bin/pest",
                "{$pluginPath}/tests",
            ];
        } else {
            $command = [
                "{$baseDir}/vendor/bin/phpunit",
                "--configuration={$pluginPath}/phpunit.xml",
            ];
        }

        // オプションの追加
        if ($filter = $this->option('filter')) {
            $command[] = "--filter={$filter}";
        }

        if ($group = $this->option('group')) {
            $command[] = "--group={$group}";
        }

        if ($this->option('coverage')) {
            if ($runner === 'pest') {
                $command[] = "--coverage";
            } else {
                $command[] = "--coverage-text";
            }
        }

        if ($this->option('parallel') && $runner === 'pest') {
            $command[] = "--parallel";
        }

        if ($this->option('stop-on-failure')) {
            $command[] = "--stop-on-failure";
        }

        // 色付き出力
        $command[] = "--colors=always";

        return $command;
    }

    /**
     * テストを実行
     */
    protected function runTests($command, $pluginName, $runner)
    {
        $this->info("🚀 Executing: " . implode(' ', $command));
        $this->newLine();

        $process = new Process($command, base_path(), null, null, null);
        $process->setTty(Process::isTtySupported());

        try {
            $exitCode = $process->run(function ($type, $buffer) {
                echo $buffer;
            });

            $this->newLine();

            // 終了コード 0 = 成功, 1 = 失敗, 2 = 警告
            if ($exitCode === 0) {
                $this->info("✅ All tests passed for plugin: {$pluginName}");
            } elseif ($exitCode === 1) {
                $this->error("❌ Tests failed for plugin: {$pluginName}");
            } elseif ($exitCode === 2) {
                $this->warn("⚠️  Tests passed with warnings for plugin: {$pluginName}");
            } else {
                $this->error("❌ Tests encountered an error for plugin: {$pluginName}");
            }

            return $exitCode;

        } catch (\Exception $e) {
            $this->error("❌ Error running tests: {$e->getMessage()}");
            
            // テストランナーが見つからない場合のヒント
            if (Str::contains($e->getMessage(), 'not found') || Str::contains($e->getMessage(), 'No such file')) {
                $this->newLine();
                $this->line("💡 Hints:");
                if ($runner === 'pest') {
                    $this->line("  - Install Pest: composer require pestphp/pest --dev");
                    $this->line("  - Or use PHPUnit: php artisan plugin:test {$pluginName} --phpunit");
                } else {
                    $this->line("  - Install PHPUnit: composer require phpunit/phpunit --dev");
                }
            }

            return 1;
        }
    }
}
