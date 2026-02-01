<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SecuritySetting;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordResetEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // パスワードリセット機能が有効かどうかをチェック
        $passwordResetEnabled = (bool) SecuritySetting::get('password_reset_enabled', false);
        
        if (!$passwordResetEnabled) {
            // パスワードリセット機能が無効の場合、404を返す
            abort(404);
        }

        return $next($request);
    }
}
