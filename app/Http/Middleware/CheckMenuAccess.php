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
        \Log::info('CheckMenuAccess: Checking access', [
            'menu_key' => $menuKey,
            'url' => $request->url(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value
        ]);
        
        $canAccess = AdminHelper::canAccessMenu($menuKey);
        
        \Log::info('CheckMenuAccess: Result', [
            'menu_key' => $menuKey,
            'can_access' => $canAccess
        ]);
        
        if (!$canAccess) {
            \Log::warning('CheckMenuAccess: Access denied', [
                'menu_key' => $menuKey,
                'url' => $request->url()
            ]);
            abort(403, 'アクセス権限がありません。');
        }

        return $next($request);
    }
}
