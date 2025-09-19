<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

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
        
        // 完了画面表示後の猶予期間をチェック
        $installCompleted = session('install_completed', false);
        $gracePeriod = $installCompleted && !$isInstalled;
        
        Log::info('CheckInstallationReady: INSTALLED=' . var_export($installed, true) . ', isInstalled=' . var_export($isInstalled, true) . ', gracePeriod=' . var_export($gracePeriod, true) . ', Route=' . $currentRoute);
        
        if (!$isInstalled && !$gracePeriod) {
            // インストール関連のルート以外はインデックスにリダイレクト
            if (!$request->is('install*') && !$request->is('install/*')) {
                Log::info('CheckInstallationReady: 未インストール - install.indexにリダイレクト');
                return redirect()->route('install.index');
            }
        } elseif ($gracePeriod) {
            // 猶予期間中は通常のアクセスを許可
            Log::info('CheckInstallationReady: 猶予期間中 - アクセス許可');
            
            // ただし、インストール画面へのアクセスは制限
            if ($request->is('install', 'install/*') && !$request->is('install/complete') && !$request->is('install/finalize')) {
                Log::info('CheckInstallationReady: 猶予期間中 - インストール画面アクセス制限');
                return redirect('/')->with('message', 'インストールは既に完了しています。');
            }
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            // ただし、完了画面は表示を許可する
            if ($request->is('install', 'install/*') && !$request->is('install/finalize') && !$request->is('install/complete')) {
                Log::info('CheckInstallationReady: インストール済み - フロントページにリダイレクト');
                return redirect('/')->with('message', 'このアプリケーションは既にインストールされています。');
            } elseif ($request->is('install/complete')) {
                Log::info('CheckInstallationReady: 完了画面へのアクセスを許可');
            }
        }

        return $next($request);
    }
}
