<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\AdminHelper;

class CheckMenuEdit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuKey)
    {
        if (!AdminHelper::canEditMenu($menuKey)) {
            abort(403, '編集権限がありません。');
        }

        return $next($request);
    }
}
