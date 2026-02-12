<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;

class CheckMenuEdit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuKey): \Symfony\Component\HttpFoundation\Response
    {
        // 権限チェック
        if (!AdminHelper::canEditMenu($menuKey)) {
            abort(403, '編集権限がありません。');
        }

        // かんたんモード時: ReadOnly/GuideOnly/HiddenのPOSTをブロック
        if (AdminModeHelper::isSimpleMode() && !AdminModeHelper::isMenuEditable($menuKey)) {
            abort(403, 'かんたんモードではこの設定を変更できません。');
        }

        return $next($request);
    }
}
