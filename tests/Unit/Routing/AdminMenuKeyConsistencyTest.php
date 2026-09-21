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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pins that GET and POST for the same admin form use the SAME menu key.
 *
 * Phase 3 audit (2026-07-12) surfaced two mismatches:
 *
 *   media.settings — GET was gated only by the parent `media` access key
 *                    (from the outer route group), while POST checked
 *                    `media.settings` for edit. That means:
 *                    (a) a user with view on `media` but view=deny on
 *                        `media.settings` could still open the settings
 *                        page, and
 *                    (b) `$menuEditable` shared into the view by
 *                        CheckMenuAccess was computed against `media`,
 *                        not `media.settings` — so the save button's
 *                        dim state could disagree with the actual POST
 *                        403 outcome.
 *
 *   members.roles  — same shape: GET only inherited parent `members`
 *                    access; POST checks `members.roles` for edit.
 *
 * Fix: mount the child access key explicitly on the GET too, so
 * CheckMenuAccess computes menuEditable for the *same* key that
 * CheckMenuEdit will 403 on. UI dim and server 403 stay in lockstep.
 */
class AdminMenuKeyConsistencyTest extends TestCase
{
    /**
     * GET route → the `check.menu.access:<child-key>` middleware it MUST
     * carry so its menuEditable computation matches its sibling POST's
     * edit key.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function alignedGetRoutes(): array
    {
        return [
            'admin.media.settings' => [
                'check.menu.access:media.settings',
                'admin.media.settings',
            ],
            'admin.members.roles' => [
                'check.menu.access:members.roles',
                'admin.members.roles',
            ],
        ];
    }

    #[DataProvider('alignedGetRoutes')]
    public function test_get_route_uses_the_same_child_menu_key_as_its_sibling_post(string $expectedMiddleware, string $routeName): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull(
            $route,
            "Route '{$routeName}' does not exist; the Phase 3 mismatch inventory is out of date.",
        );

        $this->assertContains(
            $expectedMiddleware,
            $route->middleware(),
            "Route '{$routeName}' is missing the '{$expectedMiddleware}' guard. "
            .'Without it CheckMenuAccess computes menuEditable from the parent key, '
            .'and the save button dim state can disagree with the actual POST outcome.',
        );
    }
}
