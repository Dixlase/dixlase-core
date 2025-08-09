<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CheckInstallationStatus
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

        // .envファイルが存在しない場合、.env.example からコピーして生成
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return redirect()->route('install.index');
        }

        // ファイルキャッシュをクリア（最新の.env内容を確実に読み取るため）
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($envPath, true);
        }
        clearstatcache(true, $envPath);

        $envContent = file_get_contents($envPath);
        $appKeyExists = preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches);

        // APP_KEYの設定がない、または空の場合のみ新規生成
        if (!$appKeyExists || empty(trim($matches[1]))) {
            $newKey = 'base64:' . base64_encode(random_bytes(32));
            $this->updateAppKey($newKey);
        }

        // .envファイルから直接INSTALLEDの値を確認（キャッシュ問題回避）
        $installedFromFile = false;
        
        // デバッグ: .envファイルの内容をログ出力（INSTALLEDの行のみ）
        $envLines = explode("\n", $envContent);
        $installedLines = array_filter($envLines, function($line) {
            return strpos($line, 'INSTALLED') !== false;
        });
        Log::info('CheckInstallationStatus - INSTALLED lines in .env: ' . json_encode(array_values($installedLines)));
        
        if (preg_match('/^INSTALLED=(.+)$/m', $envContent, $installedMatches)) {
            $installedValue = trim($installedMatches[1]);
            $installedFromFile = ($installedValue === 'true' || $installedValue === '1');
            Log::info('CheckInstallationStatus - INSTALLED from file: ' . $installedValue . ' (parsed as: ' . ($installedFromFile ? 'true' : 'false') . ')');
        } else {
            Log::info('CheckInstallationStatus - INSTALLED not found in .env file via regex');
        }
        
        // インストール済みでない場合
        if (!$installedFromFile) {
            Log::info('CheckInstallationStatus - インストール未完了と判定、install画面にリダイレクト');
            // インストール完了画面へのアクセスは、フラグファイルがある場合のみ許可
            if ($request->is('install/complete')) {
                $flagFile = storage_path('app/installation_complete.flag');
                if (file_exists($flagFile)) {
                    return $next($request);
                }
            }
            
            // インストール関連のルート（GET/POST問わず）は全て許可
            if ($request->is('install') || $request->is('install/*')) {
                return $next($request);
            }
            
            // インストール関連以外のルートはインデックスにリダイレクト
            return redirect()->route('install.index');
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            if ($request->is('install') || $request->is('install/*')) {
                return redirect('/')->with('message', 'このアプリケーションは既にインストールされています。');
            }
        }

        return $next($request);
    }

    /**
     * Update the APP_KEY in the .env file
     */
    protected function updateAppKey($newKey)
    {
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        // APP_KEYの設定が存在する場合、置換
        if (preg_match("/^APP_KEY=.*$/m", $envContent)) {
            $envContent = preg_replace("/^APP_KEY=.*$/m", "APP_KEY={$newKey}\n", $envContent);
        } else {
            // APP_KEYの設定が存在しない場合、追加
            $envContent .= "APP_KEY={$newKey}\n";
        }
        
        // .envファイルに書き込み
        file_put_contents($envPath, $envContent);

        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
}
