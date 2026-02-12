<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\MenuVisibility;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuKey): \Symfony\Component\HttpFoundation\Response
    {
        // 権限チェック
        if (!AdminHelper::canAccessMenu($menuKey)) {
            abort(403, 'アクセス権限がありません。');
        }

        // かんたんモード時: Hiddenメニューへのアクセスをブロック
        if (AdminModeHelper::isSimpleMode()) {
            $visibility = AdminModeHelper::getMenuVisibility($menuKey);

            if ($visibility === MenuVisibility::Hidden) {
                return redirect()->route('admin.dashboard')
                    ->with('warning', 'この機能はかんたんモードでは利用できません。詳細モードに切り替えてご利用ください。');
            }
        }

        return $next($request);
    }
}
