<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Enums\MenuVisibility;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use Closure;
use Illuminate\Http\Request;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuKey): \Symfony\Component\HttpFoundation\Response
    {
        // Permission check
        if (! AdminHelper::canAccessMenu($menuKey)) {
            abort(403, __('http/middleware/check_menu_access.no_access_permission'));
        }

        // In simple mode: Block access to hidden menus
        if (AdminModeHelper::isSimpleMode()) {
            $visibility = AdminModeHelper::getMenuVisibility($menuKey);

            if ($visibility === MenuVisibility::Hidden) {
                return redirect()->route('admin.dashboard')
                    ->with('warning', __('http/middleware/check_menu_access.feature_unavailable_in_simple_mode'));
            }
        }

        return $next($request);
    }
}
