<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use function \App\Helpers\canAccessMenu;
use App\Helpers\AdminHelper;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuKey)
    {
        $canAccess = AdminHelper::canAccessMenu($menuKey);
        
        if (!$canAccess) {
            abort(403, 'アクセス権限がありません。');
        }

        return $next($request);
    }
}
