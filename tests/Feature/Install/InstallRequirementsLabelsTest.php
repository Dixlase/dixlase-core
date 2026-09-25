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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The requirements screen states a floor for the recommended PHP
 * settings, not an exact value — 300s satisfies "60s", and the old label
 * read as though it did not.
 *
 * The wording lives in its own key because `install/common.recommended`
 * is the bare word, shared with the recommended-extension rows: appending
 * "or more" there would turn "Redis (Recommended)" into
 * "Redis (Recommended: or more)". These tests pin both sides of that.
 */
class InstallRequirementsLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_recommended_php_setting_reads_as_a_floor(): void
    {
        $response = $this->get('/install');

        $response->assertStatus(200);
        $response->assertSee('max_execution_time');
        $response->assertSee('Recommended: 60s or more');
    }

    public function test_the_japanese_label_reads_as_a_floor(): void
    {
        $response = $this->withSession(['install_locale' => 'ja'])->get('/install');

        $response->assertStatus(200);
        $response->assertSee('推奨: 60s 以上', false);
    }

    public function test_the_recommended_extension_rows_keep_the_bare_word(): void
    {
        $response = $this->get('/install');

        // The regression this change is designed to avoid.
        $response->assertSee('(Recommended):');
        $response->assertDontSee('(Recommended: or more)');
    }

    public function test_the_required_floor_is_still_thirty_seconds(): void
    {
        $response = $this->get('/install');

        $response->assertSee('required: 30s');
    }
}
