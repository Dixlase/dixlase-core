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

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The install wizard checks the same PHP floor core declares (#988). It used
 * to check 8.2 and say "PHP 8.2+" after composer.json had moved to ^8.3.
 */
class InstallPhpFloorTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_wizard_floor_matches_composer_json(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        $constraint = (string) ($composer['require']['php'] ?? '');

        $this->assertMatchesRegularExpression('/^\^(\d+\.\d+)$/', $constraint);
        preg_match('/^\^(\d+\.\d+)$/', $constraint, $matches);

        $this->assertSame(
            $matches[1].'.0',
            InstallController::MINIMUM_PHP_VERSION,
            'The install wizard and composer.json state different PHP floors.'
        );
    }

    public function test_the_requirements_screen_names_the_floor(): void
    {
        $response = $this->get('/install');

        $response->assertStatus(200);
        $response->assertSee('PHP 8.3+');
        $response->assertDontSee('PHP 8.2+');
    }
}
