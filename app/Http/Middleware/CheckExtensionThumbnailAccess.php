<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Helpers\AdminHelper;
use Closure;
use Illuminate\Http\Request;

/**
 * Gate the online extension thumbnail with the key of the page it serves.
 *
 * Both thumbnail routes sit outside the check.menu.access groups. For the
 * local one that is deliberate: it serves a file already on disk, and the
 * lists that reference it are opened by several roles, so gating it would
 * blank the cards of an installed extension for the roles that may list
 * them. The online one is different -- it reaches a configured source over
 * the network on behalf of the "Add plugin" / "Add theme" pages -- so it
 * answers to the same key those pages use.
 *
 * It needs a middleware of its own rather than check.menu.access:<key>,
 * because the key depends on the route's {type} parameter, which a static
 * middleware argument cannot express (#855).
 */
class CheckExtensionThumbnailAccess
{
    /**
     * The page each extension type's thumbnail is listed on.
     */
    private const MENU_KEYS = [
        'plugin' => 'settings.plugins.add',
        'theme' => 'settings.themes.add',
    ];

    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $type = (string) $request->route('type');

        // An unknown type is the controller's 404, not a 403: answering 403
        // here would turn a bad URL into a permission message.
        $menuKey = self::MENU_KEYS[$type] ?? null;

        if ($menuKey !== null && ! AdminHelper::canAccessMenu($menuKey)) {
            abort(403, __('http/middleware/check_menu_access.no_access_permission'));
        }

        return $next($request);
    }
}
