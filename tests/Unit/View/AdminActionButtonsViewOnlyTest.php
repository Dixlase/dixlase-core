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
use Tests\TestCase;

/**
 * Pins the "disabled + dimmed, kept in layout" behaviour of the three
 * shared admin action components (save-button, delete-button, danger-zone)
 * for view-only users.
 *
 * Rationale: PR #125 hid these components entirely for view-only users,
 * which produced a layout shift between menus a role could edit and menus
 * it could not (the sticky footer stripe would disappear on some pages
 * and reappear on others). This test locks in the follow-up decision to
 * keep the components rendered but flip them to HTML `disabled` +
 * dimmed styling via <x-form-button>'s built-in `disabled:opacity-50
 * disabled:cursor-not-allowed` classes, plus a `title` tooltip.
 *
 * Server-side CheckMenuEdit still 403s if a script or curl POST tries
 * to bypass the disabled attribute; this test only covers the UI layer.
 */
class AdminActionButtonsViewOnlyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Each test flips this shared boolean explicitly; reset here so a
        // preceding test (or the default-null baseline) cannot leak in.
        View::share('menuEditable', null);
    }

    public function test_save_button_is_disabled_and_tooltipped_when_view_only(): void
    {
        View::share('menuEditable', false);
        $html = Blade::render('<x-admin.save-button />');

        // <button ... disabled ...> — HTML attribute, not the Tailwind
        // `disabled:opacity-50` utility class (which contains the same
        // substring). Anchor on the space-separated attribute form.
        $this->assertMatchesRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
        // The tooltip is the sole reason a hover-inspecting operator can
        // tell why the button is dimmed. Missing tooltip = failed fix.
        $this->assertStringContainsString(__('common.view_only_action_disabled'), $html);
    }

    public function test_save_button_is_enabled_and_untooltipped_when_editable(): void
    {
        View::share('menuEditable', true);
        $html = Blade::render('<x-admin.save-button />');

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
        $this->assertStringNotContainsString(__('common.view_only_action_disabled'), $html);
    }

    public function test_save_button_default_is_enabled_when_menu_editable_shared_variable_is_absent(): void
    {
        // Backwards-compat guard: pages not (yet) behind CheckMenuAccess
        // do not share menuEditable at all. In that case the component
        // must render the save button in its enabled, functional form —
        // NOT disabled — so admin pages that predate the middleware wiring
        // still work.
        View::share('menuEditable', null);
        $html = Blade::render('<x-admin.save-button />');

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
    }

    public function test_delete_button_is_disabled_and_tooltipped_when_view_only(): void
    {
        View::share('menuEditable', false);
        $html = Blade::render(
            '<x-admin.delete-button id_confirmation="deleteConfirm" />'
        );

        $this->assertMatchesRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
        $this->assertStringContainsString(__('common.view_only_action_disabled'), $html);
    }

    public function test_delete_button_is_enabled_when_editable(): void
    {
        View::share('menuEditable', true);
        $html = Blade::render(
            '<x-admin.delete-button id_confirmation="deleteConfirm" />'
        );

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
    }

    public function test_danger_zone_dims_all_three_action_buttons_when_view_only(): void
    {
        // All three routes present so each optional block renders and can
        // be asserted on. Real callers pass a subset (usually just delete).
        View::share('menuEditable', false);
        $html = Blade::render(<<<'BLADE'
            <x-admin.danger-zone
                unlock-route="/unlock"
                force-logout-route="/force-logout"
                delete-route="/delete"
                entity-type="member"
            />
        BLADE);

        // preg_match_all counts overlap-free hits — three action buttons
        // must each carry the `disabled` attribute. Anything below three
        // means one of them slipped through.
        $count = preg_match_all('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
        $this->assertSame(3, $count, 'expected all 3 danger-zone action buttons to render as disabled');
        $this->assertStringContainsString(__('common.view_only_action_disabled'), $html);
    }

    public function test_danger_zone_enables_all_buttons_when_editable(): void
    {
        View::share('menuEditable', true);
        $html = Blade::render(<<<'BLADE'
            <x-admin.danger-zone
                unlock-route="/unlock"
                force-logout-route="/force-logout"
                delete-route="/delete"
                entity-type="member"
            />
        BLADE);

        $this->assertDoesNotMatchRegularExpression('/<button[^>]*\sdisabled(?=[\s>=])/', $html);
    }
}
