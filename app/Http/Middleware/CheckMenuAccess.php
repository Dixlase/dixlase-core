<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
