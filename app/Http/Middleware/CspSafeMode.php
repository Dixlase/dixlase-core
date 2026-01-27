<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CspSafeMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ?safe=1 パラメータをチェック
        if ($request->query('safe') === '1') {
            // ログインしているかチェック
            if (auth()->check()) {
                // セーフモードフラグをセッションに保存
                session(['csp_safe_mode' => true]);
                
                // ログに記録
                \Log::channel('admin_activity')->warning('CSPセーフモード有効化', [
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'unknown',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'timestamp' => now(),
                ]);
            }
        }
        
        return $next($request);
    }
}
