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

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * `/test-front-log` was a development helper registered on the public front
 * routes with no auth and no environment check. Every hit wrote the caller's
 * URL and User-Agent into the front activity and error logs, and the JSON
 * response carried a link into the admin log viewer -- disclosing the
 * randomised admin path the installer generates to keep the panel out of sight.
 */
class FrontDebugRouteRemovedTest extends TestCase
{
    public function test_the_front_log_test_route_is_not_registered(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('test.front.log'));

        $this->assertFalse(
            collect(Route::getRoutes())->contains(fn ($route) => $route->uri() === 'test-front-log'),
            'the /test-front-log route must not be registered'
        );
    }
}
