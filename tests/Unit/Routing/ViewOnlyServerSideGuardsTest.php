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

namespace Tests\Unit\Routing;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pins the `check.menu.edit:<menu-key>` server-side guard on every admin
 * POST/PATCH/PUT/DELETE route that mutates state, so a view-only user
 * cannot silently bypass the UI-layer dim (see AdminActionButtonsView
 * OnlyTest) by hitting the route with curl / a script.
 *
 * The middleware itself (CheckMenuEdit) is covered by
 * CheckMenuAccessSharesEditableTest — this test only asserts that the
 * middleware is actually wired into the correct set of routes.
 *
 * The route list here was seeded by the Phase 3 audit (2026-07-12) as
 * the set of modify routes that were guarded only by `check.menu.access`,
 * which lets a view-only user through. Every route below MUST also
 * carry the matching `check.menu.edit:<menu-key>` — this test fails
 * loudly if a future refactor drops one.
 */
class ViewOnlyServerSideGuardsTest extends TestCase
{
    /**
     * Route-name → required `check.menu.edit:<key>` middleware.
     *
     * @return array<string, string>
     */
    public static function guardedRoutes(): array
    {
        return [
            // Members CRUD — POST store / update were access-guarded only
            // pre-Phase-3, which meant a view-only role could create or
            // edit any member by submitting the form directly. High-risk
            // regression; the middleware must stay.
            'admin.members.store' => 'check.menu.edit:members.create_edit',
            'admin.members.update' => 'check.menu.edit:members.create_edit',

            // Mail server test operations — send a real email, or open an
            // SMTP connection, or clear a test session. Not destructive
            // to persistent data, but definitely "modify" from the point
            // of view of the outside world, so gated by the same key as
            // the mail settings themselves.
            'admin.settings.base.mail.test-mail' => 'check.menu.edit:settings.base.mail',
            'admin.settings.base.mail.test-connection' => 'check.menu.edit:settings.base.mail',
            'admin.settings.base.mail.clear-test-session' => 'check.menu.edit:settings.base.mail',

            // CAPTCHA widget-validation + clear-test — same reasoning as
            // the mail test routes: they hit a third-party service or
            // clear per-user state, so a view-only role should not fire
            // them.
            'admin.settings.security.captcha.validate-widget' => 'check.menu.edit:settings.security.captcha',
            'admin.settings.security.captcha.clear-test' => 'check.menu.edit:settings.security.captcha',
        ];
    }

    /**
     * @dataProvider guardedRoutesProvider
     */
    public function test_route_carries_the_check_menu_edit_middleware(string $expectedMiddleware, string $routeName): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull(
            $route,
            "Route '{$routeName}' does not exist; the Phase 3 route inventory is out of date.",
        );

        $this->assertContains(
            $expectedMiddleware,
            $route->middleware(),
            "Route '{$routeName}' is missing the '{$expectedMiddleware}' guard. "
            .'Without it a view-only member could POST to this endpoint and mutate state.',
        );
    }

    public static function guardedRoutesProvider(): array
    {
        $out = [];
        foreach (self::guardedRoutes() as $name => $mw) {
            // PHPUnit dataProvider passes args in order — put middleware first,
            // route name second, to line up with the test method signature and
            // give a readable failure heading (dataset name = route name).
            $out[$name] = [$mw, $name];
        }

        return $out;
    }
}
