<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class AppUninstall extends Command
{
    /**
     * コマンドの名前とシグネチャ
     *
     * @var string
     */
    protected $signature = 'app:uninstall {--force : 対話なしで即座にアンインストールを実行}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'アプリケーションをアンインストールし、環境設定とデータベースを削除します。';

    /**
     * コマンドの実行処理
     */
    public function handle()
    {
        $force = $this->option('force');
        
        if (!$force) {
            $this->warn('⚠️  注意: この操作はアプリケーションを完全に削除します！');
            if (!$this->confirm('本当にアンインストールしますか？', false)) {
                $this->info('アンインストールをキャンセルしました。');
                return;
            }
        } else {
            $this->warn('⚠️  --forceオプションが指定されました。対話なしでアンインストールを実行します。');
        }

        // ✅ 1. データベースの処理（優先）
        if (!$force && $this->confirm('データベースをバックアップしますか？', true)) {
            $dbName = env('DB_DATABASE');
            $dumpFile = base_path("database/backups/{$dbName}_" . now()->format('Ymd_His') . ".sql");
            $this->dumpDatabase($dumpFile);
            $this->info("データベースをバックアップしました: {$dumpFile}");
        } elseif ($force) {
            $this->info('--forceオプション: データベースバックアップをスキップします。');
        }

        if (!$force && $this->confirm('データベースの全テーブルを削除しますか？')) {
            $this->dropAllTables();
            $this->info('データベースの全テーブルを削除しました。');
        } elseif ($force) {
            $this->dropAllTables();
            $this->info('データベースの全テーブルを削除しました。');
        }

        // ✅ 2. シンボリックリンクの削除
        $this->removeSymlinks();

        // ✅ 3. キャッシュのクリア
        $this->clearCache();

        // ✅ 4. `.env` ファイルの処理（最後）
        $envPath = base_path('.env');
        
        if (File::exists($envPath)) {
            $backupPath = base_path('.env.backup_' . now()->format('Ymd_His'));
            
            if (!$force && $this->confirm('.env を削除せずにバックアップしますか？', true)) {
                try {
                    File::copy($envPath, $backupPath);
                    $this->info("✅ .env をバックアップしました: {$backupPath}");
                } catch (\Exception $e) {
                    $this->error("❌ バックアップエラー: " . $e->getMessage());
                }
            } elseif ($force) {
                $this->info('--forceオプション: .envバックアップをスキップします。');
            }
            
            // アンインストール後は.envファイルを削除（完全なアンインストール）
            $deleted = false;
            
            // 方法1: Laravel File::delete()
            try {
                $deleteResult = File::delete($envPath);
                if ($deleteResult && !File::exists($envPath)) {
                    $deleted = true;
                }
            } catch (\Exception $e) {
                // File::delete()が失敗した場合はログに記録
            }
            
            // 方法2: PHP unlink()（File::delete()が失敗した場合）
            if (!$deleted && File::exists($envPath)) {
                try {
                    $unlinkResult = unlink($envPath);
                    if ($unlinkResult && !File::exists($envPath)) {
                        $deleted = true;
                    }
                } catch (\Exception $e) {
                    // unlink()も失敗した場合
                }
            }
            
            if (!$deleted) {
                $this->error("❌ .env の削除に失敗しました。手動で削除してください: {$envPath}");
            } else {
                $this->info("✅ .env ファイルを完全に削除しました。");
                $this->info("ℹ️ インストール時に .env.example から新しい .env が作成されます。");
            }
        } else {
            $this->warn("⚠️ .env ファイルが存在しません。");
        }

        // ✅ アンインストール完了
        $this->info('✅ アンインストールが完了しました！');
        $this->line('');
        $this->info('📝 再インストール時の注意:');
        $this->info('   すべてのキャッシュがクリアされているため、');
        $this->info('   追加のコマンド実行なしで再インストールが可能です。');
    }

    /**
     * データベースのダンプを作成
     */
    private function dumpDatabase(string $dumpFile)
    {
        try {
            $dbConnection = env('DB_CONNECTION');
            $dbHost = env('DB_HOST');
            $dbPort = env('DB_PORT');
            $dbDatabase = env('DB_DATABASE');
            $dbUsername = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');

            if (!File::exists(dirname($dumpFile))) {
                File::makeDirectory(dirname($dumpFile), 0755, true);
            }

            $command = match ($dbConnection) {
                'mysql' => "mysqldump -h {$dbHost} -P {$dbPort} -u {$dbUsername} --password={$dbPassword} {$dbDatabase} > {$dumpFile}",
                'pgsql' => "PGPASSWORD={$dbPassword} pg_dump -h {$dbHost} -p {$dbPort} -U {$dbUsername} -F c -b -v -f {$dumpFile} {$dbDatabase}",
                'sqlite' => "cp {$dbDatabase} {$dumpFile}",
                default => null,
            };

            if ($command) {
                exec($command, $output, $returnVar);
                if ($returnVar !== 0) {
                    throw new Exception("データベースのダンプに失敗しました。");
                }
            } else {
                throw new Exception("対応していないデータベースドライバ: {$dbConnection}");
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::error($e->getMessage());
        }
    }

    /**
     * データベースの全テーブルを削除
     */
    private function dropAllTables()
    {
        try {
            $dbConnection = DB::connection();
            $dbType = config('database.default');

            if ($dbType === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = reset($table);
                    DB::statement("DROP TABLE {$tableName}");
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($dbType === 'pgsql') {
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                foreach ($tables as $table) {
                    DB::statement("DROP TABLE IF EXISTS {$table->tablename} CASCADE");
                }
            } elseif ($dbType === 'sqlite') {
                // SQLiteの場合はデータベースファイル自体を削除
                $dbPath = database_path('database.sqlite');
                if (File::exists($dbPath)) {
                    File::delete($dbPath);
                    $this->info("SQLiteデータベースファイルを削除しました: {$dbPath}");
                }
            } else {
                throw new Exception("対応していないデータベースドライバ: {$dbType}");
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::error($e->getMessage());
        }
    }

    /**
     * storage・テーマ・プラグインのシンボリックリンクを削除
     */
    private function removeSymlinks()
    {
        // ✅ storage のシンボリックリンク削除
        $storageLink = public_path('storage');
        if (file_exists($storageLink) || is_link($storageLink)) {
            unlink($storageLink);
            $this->info("✔️ storage のシンボリックリンクを削除しました。");
        }

        // ✅ テーマアセットのシンボリックリンク削除
        $themeLink = public_path('assets/theme');
        if (file_exists($themeLink) || is_link($themeLink)) {
            unlink($themeLink);
            $this->info("✔️ テーマアセットのシンボリックリンクを削除しました。");
        }

        // ✅ プラグインアセットのシンボリックリンク削除
        $pluginsDir = public_path('assets/plugins');
        if (file_exists($pluginsDir) && is_dir($pluginsDir)) {
            $pluginLinks = scandir($pluginsDir);
            foreach ($pluginLinks as $pluginLink) {
                if ($pluginLink !== '.' && $pluginLink !== '..') {
                    $pluginPath = "{$pluginsDir}/{$pluginLink}";
                    if (file_exists($pluginPath) || is_link($pluginPath)) {
                        unlink($pluginPath);
                        $this->info("✔️ プラグインアセットのシンボリックリンクを削除しました: {$pluginLink}");
                    }
                }
            }
        }
    }

    /**
     * キャッシュのクリア
     */
    private function clearCache()
    {
        // ✅ キャッシュドライバが database の場合、一時的に file に変更
        $originalCacheDriver = config('cache.default');
        if ($originalCacheDriver === 'database') {
            config(['cache.default' => 'file']);
        }

        try {
            // 基本的なキャッシュクリア
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            // 追加のクリアコマンド（再インストール問題対策）
            Artisan::call('clear-compiled');
            Artisan::call('optimize:clear');
            
            $this->info('✔️ 基本キャッシュをクリアしました。');
            
            // Composer autoload の再生成
            $this->info('🔄 Composer autoload を再生成中...');
            $composerResult = shell_exec('composer dump-autoload 2>&1');
            if ($composerResult !== null) {
                $this->info('✔️ Composer autoload を再生成しました。');
            } else {
                $this->warn('⚠️ Composer autoload の再生成をスキップしました（composerコマンドが見つからない）。');
            }
            
            // アンインストール後はセッションドライバをfileに変更してからキャッシュ再生成
            if ($this->isEnvComplete()) {
                // .envのセッションドライバをfileに変更
                $this->updateEnvSessionDriver();
                
                Artisan::call('config:cache');
                $this->info('✔️ 設定キャッシュを再生成しました（セッションドライバ: file）。');
            } else {
                $this->info('ℹ️ .envが不完全なため、config:cacheをスキップしました。');
            }
            
        } catch (\Exception $e) {
            $this->error('キャッシュのクリア中にエラーが発生しました: ' . $e->getMessage());
        }

        // ✅ 元のキャッシュドライバに戻す
        config(['cache.default' => $originalCacheDriver]);
    }

    /**
     * .envファイルのセッションドライバをfileに変更
     */
    private function updateEnvSessionDriver()
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return;
        }

        try {
            $envContent = file_get_contents($envPath);
            
            // SESSION_DRIVERをfileに変更
            $envContent = preg_replace(
                '/^SESSION_DRIVER=.*$/m',
                'SESSION_DRIVER=file',
                $envContent
            );
            
            // SESSION_DRIVERが存在しない場合は追加
            if (!preg_match('/^SESSION_DRIVER=/m', $envContent)) {
                $envContent .= "\nSESSION_DRIVER=file\n";
            }
            
            file_put_contents($envPath, $envContent);
            $this->info('✔️ セッションドライバをfileに変更しました。');
            
        } catch (\Exception $e) {
            $this->warn('⚠️ セッションドライバの変更に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * .envファイルが完全かどうかをチェック
     */
    private function isEnvComplete(): bool
    {
        $requiredKeys = ['APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];
        
        foreach ($requiredKeys as $key) {
            if (empty(env($key))) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * .envファイルの値を更新
     */
    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return;
        }
        
        $envContent = File::get($envPath);
        
        // 既存のキーがある場合は更新、ない場合は追加
        if (preg_match("/^{$key}=.*$/m", $envContent)) {
            $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }
        
        File::put($envPath, $envContent);
    }
}
