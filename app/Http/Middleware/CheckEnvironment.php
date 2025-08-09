<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckEnvironment
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
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

        return $next($request);
    }
}
