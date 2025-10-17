<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckInstallationReady
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションから言語を設定
        if (session()->has('install_locale')) {
            app()->setLocale(session('install_locale'));
        }

        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        try {
            // .envファイルが存在しない場合、.env.example からコピーして生成
            if (!file_exists($envPath)) {
                if (file_exists($envExamplePath)) {
                    $copied = copy($envExamplePath, $envPath);
                    if ($copied) {
                        chmod($envPath, 0664);
                        Log::info('.env file was created from .env.example');
                    } else {
                        Log::error('Failed to copy .env.example to .env');
                        throw new \RuntimeException('Failed to create .env file');
                    }
                } else {
                    Log::error('.env.example file not found');
                    throw new \RuntimeException('.env.example file not found. Please create one.');
                }
            }

            // .envファイルの内容を取得
            $envContent = file_get_contents($envPath);
            if ($envContent === false) {
                Log::error('Failed to read .env file');
                throw new \RuntimeException('Failed to read .env file');
            }

            // APP_KEYが設定されているか確認
            if (!preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches) || empty(trim($matches[1] ?? ''))) {
                // 新しいAPP_KEYを生成
                $newKey = 'base64:' . base64_encode(random_bytes(32));
                
                // .envファイルを更新
                $updatedContent = preg_replace(
                    '/^APP_KEY=.*$/m',
                    'APP_KEY=' . $newKey,
                    $envContent,
                    -1,
                    $count
                );
                
                // マッチしなかった場合は追記
                if ($count === 0) {
                    $updatedContent .= "\nAPP_KEY=" . $newKey . "\n";
                }
                
                // ファイルに書き込み
                $written = file_put_contents($envPath, $updatedContent, LOCK_EX);
                if ($written === false) {
                    Log::error('Failed to write to .env file');
                    throw new \RuntimeException('Failed to update APP_KEY in .env file');
                }
                
                Log::info('APP_KEY was generated and saved to .env');
                
                // 設定をリフレッシュ
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($envPath, true);
                }
            }
        } catch (\Exception $e) {
            Log::error('Environment setup error: ' . $e->getMessage());
            throw $e;
        }

        // インストール状態チェック（.envの設定を優先）
        $installed = env('INSTALLED') ?? config('app.installed');
        $currentRoute = $request->route() ? $request->route()->getName() : 'unknown';
        
        // より厳密な判定
        $isInstalled = ($installed === 'true' || $installed === true);
        
        // マイグレーション完了チェック
        $debugInfo = [];
        $isMigrated = $this->checkMigrationCompleted($debugInfo);
        
        Log::info('CheckInstallationReady: INSTALLED=' . var_export($installed, true) . ', isInstalled=' . var_export($isInstalled, true) . ', isMigrated=' . var_export($isMigrated, true) . ', Route=' . $currentRoute);
        Log::info('CheckInstallationReady: Debug Info=' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        
        if (!$isInstalled) {
            // 未インストール状態の処理
            
            if ($request->is('install*') || $request->is('install/*')) {
                // installルート内でのアクセス
                
                if ($request->is('install/complete')) {
                    // 完了画面へのアクセス
                    if (!$isMigrated) {
                        // マイグレーション未完了なのに完了画面にアクセス → 初期画面へ
                        Log::info('CheckInstallationReady: マイグレーション未完了 - install.indexにリダイレクト');
                        return redirect()->route('install.index');
                    }
                    // マイグレーション完了済みなら完了画面表示を許可
                    Log::info('CheckInstallationReady: 完了画面表示を許可');
                    if ($request->hasSession()) {
                        session(['install_process_completed' => true]);
                    }
                    // 完了画面自体なので、そのまま通過させる
                    
                } elseif ($request->is('install/finalize')) {
                    // finalize処理は常に許可（POSTリクエスト）
                    Log::info('CheckInstallationReady: finalize処理を許可');
                    
                } else {
                    // その他のインストールフロー（index, environment, settings, database, confirm）
                    if ($isMigrated) {
                        // マイグレーション完了済み → 完了画面へリダイレクト
                        Log::info('CheckInstallationReady: マイグレーション完了済み - 完了画面にリダイレクト');
                        session(['install_process_completed' => true]);
                        return redirect()->route('install.complete');
                    }
                    // マイグレーション未完了ならインストールフロー続行を許可
                    Log::info('CheckInstallationReady: インストールフロー続行を許可');
                }
                
            } else {
                // install以外のルート（フロントページなど）へのアクセス
                
                if ($isMigrated) {
                    // マイグレーション完了 → 完了画面へ
                    // ただし、既に完了画面へのリダイレクト中でなければ
                    // セッションが利用可能な場合のみチェック
                    if ($request->hasSession() && !$request->session()->has('_redirect_to_complete')) {
                        Log::info('CheckInstallationReady: マイグレーション完了 - 完了画面にリダイレクト');
                        session(['install_process_completed' => true]);
                        $request->session()->put('_redirect_to_complete', true);
                        return redirect()->route('install.complete');
                    } elseif ($request->hasSession()) {
                        // リダイレクトループ防止: すでにリダイレクト済みの場合は通過
                        Log::info('CheckInstallationReady: リダイレクトループ防止 - 通過');
                    } else {
                        // セッションが利用できない場合はリダイレクト
                        Log::info('CheckInstallationReady: セッション未開始 - 完了画面にリダイレクト');
                        return redirect()->route('install.complete');
                    }
                } else {
                    // マイグレーション未完了 → インストール開始画面へ
                    Log::info('CheckInstallationReady: 未インストール - install.indexにリダイレクト');
                    return redirect()->route('install.index');
                }
            }
            
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            if ($request->is('install*') || $request->is('install/*')) {
                Log::info('CheckInstallationReady: インストール済み - フロントページにリダイレクト');
                return redirect('/')->with('message', 'このアプリケーションは既にインストールされています。');
            }
        }

        return $next($request);
    }
    
    /**
     * マイグレーションが完了しているかチェック
     * 
     * 提案1（migrationsテーブル）+ 提案3（管理者チェック）の統合版
     * 
     * @param array $debugInfo デバッグ情報を格納する配列（参照渡し）
     */
    private function checkMigrationCompleted(array &$debugInfo = []): bool
    {
        try {
            // ステップ1: データベース接続をチェック
            $dbName = DB::connection()->getDatabaseName();
            $debugInfo['step1_db_connection'] = $dbName ? "OK ({$dbName})" : 'NG';
            
            if (!$dbName) {
                Log::info('CheckInstallationReady: データベース接続なし');
                return false;
            }
            
            // ステップ2: migrationsテーブルが存在するかチェック（Laravel標準）
            // ※ 直接SQL実行でインストールされた場合はmigrationsテーブルがない場合があるため
            //    存在しない場合はスキップして次のチェックに進む
            // テーブルプレフィックスを考慮: dxl_migrations または migrations
            $hasMigrationsTable = DB::getSchemaBuilder()->hasTable('migrations');
            $debugInfo['step2_migrations_table'] = $hasMigrationsTable ? 'OK' : 'SKIP (直接SQL実行の可能性)';
            
            if ($hasMigrationsTable) {
                // ステップ3: マイグレーション実行レコード数をチェック
                try {
                    $migrationCount = DB::table('migrations')->count();
                    $minRequiredMigrations = 15;
                    $debugInfo['step3_migration_count'] = "{$migrationCount}件 (必要: {$minRequiredMigrations})";
                    
                    if ($migrationCount < $minRequiredMigrations) {
                        Log::info("CheckInstallationReady: マイグレーション数不足 (実行済み: {$migrationCount}, 必要: {$minRequiredMigrations})");
                        return false;
                    }
                    
                    Log::info("CheckInstallationReady: マイグレーション実行確認 ({$migrationCount}件)");
                } catch (\Exception $e) {
                    // テーブル名の問題などでエラーが発生した場合はスキップ
                    $debugInfo['step3_migration_count'] = 'ERROR: ' . $e->getMessage();
                    Log::info("CheckInstallationReady: migrationsテーブルアクセスエラー - 他のチェックで判定");
                }
            } else {
                $debugInfo['step3_migration_count'] = 'SKIP (migrationsテーブル不在)';
                Log::info("CheckInstallationReady: migrationsテーブル不在 - 他のチェックで判定");
            }
            
            // ステップ4: 主要テーブルの存在チェック（念のため）
            $requiredTables = ['members', 'base_settings', 'themes'];
            $tableStatus = [];
            
            foreach ($requiredTables as $table) {
                $exists = DB::getSchemaBuilder()->hasTable($table);
                $tableStatus[$table] = $exists ? 'OK' : 'NG';
                
                if (!$exists) {
                    Log::info("CheckInstallationReady: 主要テーブル '{$table}' が存在しません");
                    $debugInfo['step4_tables'] = $tableStatus;
                    return false;
                }
            }
            $debugInfo['step4_tables'] = $tableStatus;
            
            // ステップ5: 管理者ユーザーが存在するかチェック（初期データ投入の証拠）
            // role=10: SUPER_ADMIN, role=9: ADMIN
            $adminCount = DB::table('members')
                ->whereIn('role', [9, 10])
                ->count();
            $debugInfo['step5_admin_users'] = "{$adminCount}人";
            
            if ($adminCount === 0) {
                Log::info('CheckInstallationReady: 管理者ユーザーが存在しません（初期データ未投入）');
                return false;
            }
            
            Log::info('CheckInstallationReady: 管理者ユーザー存在確認');
            
            // ステップ6: base_settingsに基本データが存在するかチェック（さらなる確認）
            $hasSiteName = DB::table('base_settings')
                ->where('name', 'site_name')
                ->exists();
            $debugInfo['step6_site_name'] = $hasSiteName ? 'OK' : 'NG';
            
            if (!$hasSiteName) {
                Log::info('CheckInstallationReady: base_settingsに初期データが存在しません');
                return false;
            }
            
            $debugInfo['result'] = '✅ ALL PASSED';
            Log::info('CheckInstallationReady: ✅ マイグレーション完了を確認（全チェック通過）');
            return true;
            
        } catch (\Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            Log::info('CheckInstallationReady: マイグレーションチェックエラー - ' . $e->getMessage());
            return false;
        }
    }
}
