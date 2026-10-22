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
 * <x-form-checkbox> decided the checked state from $value, which defaults to
 * 1, so every checkbox rendered checked and the $checked prop could never
 * make a difference -- it was already true by the time it was read. $value is
 * the submitted value; the `value` attribute was separately hard-coded to 1,
 * so the prop was read for one job and used for another.
 *
 * The visible consequence was the admin login screen's "remember me" box
 * arriving ticked, which opts the member into a persistent login by default.
 */
class FormCheckboxCheckedStateTest extends TestCase
{
    public function test_a_checkbox_is_not_checked_by_default(): void
    {
        $html = Blade::render('<x-form-checkbox name="remember" label="common.password" />');

        $this->assertStringNotContainsString('checked', $html);
    }

    public function test_the_checked_prop_checks_it(): void
    {
        $html = Blade::render('<x-form-checkbox name="remember" :checked="true" label="common.password" />');

        $this->assertMatchesRegularExpression('/<input[^>]*\bchecked\b/', $html);
    }

    /**
     * The case the old condition could not express: a checkbox that submits a
     * value and is not checked. That is the ordinary one.
     */
    public function test_a_value_does_not_check_it(): void
    {
        $html = Blade::render('<x-form-checkbox name="agree" value="yes" label="common.password" />');

        $this->assertStringContainsString('value="yes"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function test_the_value_prop_is_submitted(): void
    {
        $this->assertStringContainsString(
            'value="1"',
            Blade::render('<x-form-checkbox name="remember" label="common.password" />'),
            'The default submitted value stays 1.'
        );

        $this->assertStringContainsString(
            'value="7"',
            Blade::render('<x-form-checkbox name="level" value="7" label="common.password" />')
        );
    }

    /**
     * The login screen is the one caller in this tree, and it passes neither
     * prop -- so this is what fixing the component changes for a member.
     */
    public function test_the_login_screen_does_not_pre_tick_remember_me(): void
    {
        $html = Blade::render(
            '<x-form-checkbox id="remember_me" name="remember" label="common.password" />'
        );

        $this->assertStringContainsString('name="remember"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }
}
