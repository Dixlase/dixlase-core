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
        if (!file_exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        // .envファイルの内容を直接取得
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        $appKeyExists = preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches);

        // APP_KEYの設定がない、または空の場合のみ新規生成
        if (!$appKeyExists || empty(trim($matches[1]))) {
            $newKey = 'base64:' . base64_encode(random_bytes(32));
            $this->updateAppKey($newKey);
        }

        // インストール済みでない場合
        if (env('INSTALLED') !== true) {
            // インストール関連のルート以外はインデックスにリダイレクト
            if (!$request->is('install*') && !$request->is('install/*')) {
                return redirect()->route('install.index');
            }
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            if ($request->is('install', 'install/*') && !$request->is('install/finalize')) {
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
