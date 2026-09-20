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

namespace Tests\Unit\View;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The show/hide toggle of <x-form-text> positions its eye icon absolutely
 * inside a wrapper. The input itself is capped by the size classes
 * (input-sm/md/lg/xl → max-width), so the wrapper must carry the same
 * cap — otherwise the icon lands at the right edge of the whole row.
 */
class FormTextPasswordToggleTest extends TestCase
{
    public function test_toggle_wrapper_takes_the_input_size_class(): void
    {
        $html = Blade::render('<x-form-text name="secret" type="password" :showPasswordToggle="true" class="input-common input-xl" />');

        $this->assertMatchesRegularExpression('/<div[^>]*class="relative input-xl"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*\binput-xl\b/', $html);
        $this->assertStringContainsString('fa-eye', $html);
    }

    public function test_toggle_wrapper_stays_full_width_without_a_size_class(): void
    {
        $html = Blade::render('<x-form-text name="db_password" type="password" :showPasswordToggle="true" class="input-full" />');

        $this->assertMatchesRegularExpression('/<div[^>]*class="relative"/', $html);
    }

    public function test_no_wrapper_without_the_toggle(): void
    {
        $html = Blade::render('<x-form-text name="plain" class="input-common input-xl" />');

        $this->assertStringNotContainsString('showPassword', $html);
        $this->assertStringNotContainsString('fa-eye', $html);
    }
}
