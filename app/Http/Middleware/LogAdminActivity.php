<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use \App\Models\Member;



class LogAdminActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        if ($user instanceof Member) {
            Log::channel('admin_activity')->info('管理画面操作', [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
                'method' => $request->method(),
                'uri' => $request->path(),
                'route' => Route::currentRouteName(),
                'controller' => Route::currentRouteAction(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'time' => now()->toDateTimeString(),
            ]);
        }

        return $response;
    }
}
