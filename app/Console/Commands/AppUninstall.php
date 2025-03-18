<?php

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
    protected $signature = 'app:uninstall';

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
        $this->warn('⚠️  注意: この操作はアプリケーションを完全に削除します！');
        if (!$this->confirm('本当にアンインストールしますか？', false)) {
            $this->info('アンインストールをキャンセルしました。');
            return;
        }

        // ✅ `.env` ファイルの処理
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $backupPath = base_path('.env.backup_' . now()->format('Ymd_His'));
            if ($this->confirm('.env を削除せずにバックアップしますか？')) {
                File::move($envPath, $backupPath);
                $this->info(".env をバックアップしました: {$backupPath}");
            } else {
                File::delete($envPath);
                $this->info(".env を削除しました。");
            }
        }

        // ✅ データベースの処理
        if ($this->confirm('データベースをバックアップしますか？')) {
            $dbName = env('DB_DATABASE');
            $dumpFile = base_path("database/backups/{$dbName}_" . now()->format('Ymd_His') . ".sql");
            $this->dumpDatabase($dumpFile);
            $this->info("データベースをバックアップしました: {$dumpFile}");
        }

        if ($this->confirm('データベースの全テーブルを削除しますか？')) {
            $this->dropAllTables();
            $this->info('データベースの全テーブルを削除しました。');
        }


        // ✅ シンボリックリンクの削除
        $this->removeSymlinks();

        // ✅ キャッシュのクリア
        $this->clearCache();

        // ✅ アンインストール完了
        $this->info('✅ アンインストールが完了しました！');
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
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:cache');

            $this->info('✔️ キャッシュをクリアしました。');
        } catch (\Exception $e) {
            $this->error('キャッシュのクリア中にエラーが発生しました: ' . $e->getMessage());
        }

        // ✅ 元のキャッシュドライバに戻す
        config(['cache.default' => $originalCacheDriver]);
    }
}
