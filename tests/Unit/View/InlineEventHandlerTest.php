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

namespace Tests\Unit\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * The shared button and select components must not emit an inline event
 * handler, and the two-factor e-mail challenge must not carry one.
 *
 * CspBuilder sets script-src-attr to 'none' in every preset but development
 * with block_script_attr disabled, so the browser refuses an inline handler.
 * A component that renders one hands every caller a control that cannot work.
 */
class InlineEventHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('menuEditable', null);
        View::share('errors', new ViewErrorBag());
    }

    public function test_the_button_component_drops_an_onclick_attribute(): void
    {
        $html = Blade::render('<x-form-button type="button" label="Save" onclick="doThing()" />');

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('Save', $html);
    }

    public function test_the_button_component_still_binds_alpine_through_x_click(): void
    {
        $html = Blade::render('<x-form-button type="button" label="Save" xClick="save()" />');

        $this->assertStringContainsString('@click="save()"', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_the_select_component_renders_a_change_binding_not_an_inline_handler(): void
    {
        $html = Blade::render(
            '<x-form-select name="kind" :options="[\'a\' => \'A\']" onchange="reload()" />'
        );

        $this->assertStringContainsString('@change="reload()"', $html);
        $this->assertStringNotContainsString('onchange', $html);
    }

    public function test_the_save_button_takes_no_onclick(): void
    {
        $html = Blade::render('<x-admin.save-button onclick="doThing()" />');

        $this->assertStringNotContainsString('onclick', $html);
    }

    /**
     * The resend button's click is handled by
     * resources/src/components/two-fa/js/email-challenge.js, which
     * resources/src/common/js/app.js imports and load_auth_assets() puts on
     * the page. The view must not also carry a handler attribute.
     */
    public function test_the_two_fa_email_challenge_view_carries_no_inline_handler(): void
    {
        $source = file_get_contents(resource_path('views/two-fa/email-challenge.blade.php'));

        $this->assertIsString($source);
        $this->assertDoesNotMatchRegularExpression(
            '/\bon(?:click|change|submit|input|paste|focus|blur|error|load)\s*=\s*["\']/i',
            $source
        );
        $this->assertStringContainsString('id="resend-button"', $source);
        $this->assertStringContainsString('"resendAction"', $source);
    }
}
