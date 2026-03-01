<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckInstallationReady
{
    /**
     * インストールチェックから除外するパス
     */
    protected array $excludedPaths = [
        'csp-report',              // CSP違反レポートエンドポイント
        '_boost/*',                // MCP/Windsurf開発ツール
        'install/verify-mail/*',   // インストール中のメール受信確認
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 除外パスのチェック（CSPレポート等）
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // インストール状態を事前チェック（ログ出力を最小限にするため）
        $installed = env('INSTALLED') ?? config('app.installed');
        $isInstalled = ($installed === 'true' || $installed === true);
        
        // インストール完了後はログを出力しない
        
        // インストール中はセッションドライバーをfileに切り替え
        if (!$isInstalled) {
            $this->ensureFileSessionDriver();
        }
        
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
                        Log::channel('install')->info('.env file was created from .env.example');
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
                
                Log::channel('install')->info('APP_KEY was generated and saved to .env');
                
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
        // 既に上でチェック済みなので再利用
        $currentRoute = $request->route() ? $request->route()->getName() : 'unknown';
        
        // マイグレーション完了チェック（未インストール時のみログ出力）
        $debugInfo = [];
        $isMigrated = $this->checkMigrationCompleted($debugInfo, !$isInstalled);
        
        // インストール完了後はログを出力しない
        
        if (!$isInstalled) {
            // 未インストール状態の処理
            
            if ($request->is('install*') || $request->is('install/*')) {
                // installルート内でのアクセス
                
                if ($request->is('install/complete')) {
                    // 完了画面へのアクセス
                    if (!$isMigrated) {
                        // マイグレーション未完了なのに完了画面にアクセス → 初期画面へ
                        return redirect()->route('install.index');
                    }
                    // マイグレーション完了済みなら完了画面表示を許可
                    if ($request->hasSession()) {
                        session(['install_process_completed' => true]);
                    }
                    // 完了画面自体なので、そのまま通過させる
                    
                } elseif ($request->is('install/finalize') || $currentRoute === 'install.finalize') {
                    // finalize処理は常に許可（POSTリクエスト）
                    
                } else {
                    // その他のインストールフロー（index, environment, settings, database, confirm）
                    if ($isMigrated) {
                        // マイグレーション完了済み → 完了画面へリダイレクト
                        session(['install_process_completed' => true]);
                        return redirect()->route('install.complete');
                    }
                    // マイグレーション未完了ならインストールフロー続行を許可
                }
                
            } else {
                // install以外のルート（フロントページなど）へのアクセス
                
                if ($isMigrated) {
                    // マイグレーション完了 → 完了画面へ
                    // ただし、既に完了画面へのリダイレクト中でなければ
                    // セッションが利用可能な場合のみチェック
                    if ($request->hasSession() && !$request->session()->has('_redirect_to_complete')) {
                        session(['install_process_completed' => true]);
                        $request->session()->put('_redirect_to_complete', true);
                        return redirect()->route('install.complete');
                    } elseif ($request->hasSession()) {
                        // リダイレクトループ防止: すでにリダイレクト済みの場合は通過
                    } else {
                        // セッションが利用できない場合はリダイレクト
                        return redirect()->route('install.complete');
                    }
                } else {
                    // マイグレーション未完了 → インストール開始画面へ
                    return redirect()->route('install.index');
                }
            }
            
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            if ($request->is('install*') || $request->is('install/*')) {
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
     * @param bool $logToInstall インストールログに出力するか（デフォルト: false）
     */
    private function checkMigrationCompleted(array &$debugInfo = [], bool $logToInstall = false): bool
    {
        try {
            // ステップ1: データベース接続をチェック
            $dbName = DB::connection()->getDatabaseName();
            $debugInfo['step1_db_connection'] = $dbName ? "OK ({$dbName})" : 'NG';
            
            if (!$dbName) {
                if ($logToInstall) {
                    Log::channel('install')->info('CheckInstallationReady: データベース接続なし');
                }
                return false;
            }
            
            // ステップ2: migrationsテーブルが存在するかチェック（Laravel標準）
            // ※ 直接SQL実行でインストールされた場合はmigrationsテーブルがない場合があるため
            //    存在しない場合はスキップして次のチェックに進む
            // テーブルプレフィックスを考慮: dls_migrations または migrations
            $hasMigrationsTable = DB::getSchemaBuilder()->hasTable('migrations');
            $debugInfo['step2_migrations_table'] = $hasMigrationsTable ? 'OK' : 'SKIP (直接SQL実行の可能性)';
            
            if ($hasMigrationsTable) {
                // ステップ3: マイグレーション実行レコード数をチェック
                try {
                    $migrationCount = DB::table('migrations')->count();
                    $minRequiredMigrations = 15;
                    $debugInfo['step3_migration_count'] = "{$migrationCount}件 (必要: {$minRequiredMigrations})";
                    
                    if ($migrationCount < $minRequiredMigrations) {
                        return false;
                    }
                } catch (\Exception $e) {
                    // テーブル名の問題などでエラーが発生した場合はスキップ
                    $debugInfo['step3_migration_count'] = 'ERROR: ' . $e->getMessage();
                }
            } else {
                $debugInfo['step3_migration_count'] = 'SKIP (migrationsテーブル不在)';
            }
            
            // ステップ4: 主要テーブルの存在チェック（念のため）
            $requiredTables = ['members', 'base_settings', 'themes'];
            $tableStatus = [];
            
            foreach ($requiredTables as $table) {
                $exists = DB::getSchemaBuilder()->hasTable($table);
                $tableStatus[$table] = $exists ? 'OK' : 'NG';
                
                if (!$exists) {
                    if ($logToInstall) {
                        Log::channel('install')->debug("CheckInstallationReady: 主要テーブル '{$table}' が存在しません");
                    }
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
                if ($logToInstall) {
                    Log::channel('install')->info('CheckInstallationReady: 管理者ユーザーが存在しません（初期データ未投入）');
                }
                return false;
            }
            
            if ($logToInstall) {
                Log::channel('install')->info('CheckInstallationReady: 管理者ユーザー存在確認');
            }
            
            // ステップ6: base_settingsに基本データが存在するかチェック（さらなる確認）
            $hasSiteName = DB::table('base_settings')
                ->where('name', 'site_name')
                ->exists();
            $debugInfo['step6_site_name'] = $hasSiteName ? 'OK' : 'NG';
            
            if (!$hasSiteName) {
                if ($logToInstall) {
                    Log::channel('install')->info('CheckInstallationReady: base_settingsに初期データが存在しません');
                }
                return false;
            }
            
            $debugInfo['result'] = '✅ ALL PASSED';
            if ($logToInstall) {
                Log::channel('install')->info('CheckInstallationReady: ✅ マイグレーション完了を確認（全チェック通過）');
            }
            return true;
            
        } catch (\Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            if ($logToInstall) {
                Log::channel('install')->info('CheckInstallationReady: マイグレーションチェックエラー - ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * インストール中はセッションドライバーをfileに切り替え
     * DBテーブルがまだ存在しない状態でguard-aware-databaseドライバーを使うとエラーになるため
     */
    private function ensureFileSessionDriver(): void
    {
        $currentDriver = config('session.driver');
        
        // 既にfileドライバーの場合は何もしない
        if ($currentDriver === 'file') {
            return;
        }
        
        // database系ドライバーの場合はfileに切り替え
        if (in_array($currentDriver, ['database', 'guard-aware-database'])) {
            config(['session.driver' => 'file']);
            
            // セッションマネージャーを再バインド
            app()->forgetInstance('session');
            app()->forgetInstance('session.store');
            
            Log::channel('install')->info('CheckInstallationReady: セッションドライバーを一時的にfileに切り替えました', [
                'original_driver' => $currentDriver
            ]);
        }
    }

    /**
     * 除外パスかどうかをチェック
     */
    protected function isExcludedPath(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedPaths as $pattern) {
            // ワイルドカードパターンをチェック
            if (str_contains($pattern, '*')) {
                $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
                if (preg_match("/^{$regex}$/", $path)) {
                    return true;
                }
            } elseif ($path === $pattern) {
                return true;
            }
        }

        return false;
    }
}
