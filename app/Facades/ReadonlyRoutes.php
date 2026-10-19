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

declare(strict_types=1);

namespace App\Facades;

use App\Services\Admin\ReadonlyRouteGuard;
use Illuminate\Support\Facades\Facade;

/**
 * ReadonlyRoutes Facade — convenience accessor for the read-only route guard.
 *
 * Used by the admin layout/sidebar to mark blocked menus and forms as
 * read-only while restricted (demo) mode is on.
 *
 * @method static bool isActive()
 * @method static bool isExempt(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool appliesTo(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static list<string> routeNames()
 * @method static bool lockedForRequest(string $routeName)
 * @method static bool menuLocked(?string $routeName)
 * @method static bool shouldBlock(\Illuminate\Http\Request $request)
 * @method static void forget()
 *
 * @see \App\Services\Admin\ReadonlyRouteGuard
 *
 * @internal For Core use only.
 */
class ReadonlyRoutes extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ReadonlyRouteGuard::class;
    }
}
