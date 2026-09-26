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

namespace Tests\Unit\Views;

use Tests\TestCase;

/**
 * Both buttons on the completion screen submit the same finalize form, so the
 * hidden redirect_to is the only difference between them. With the site
 * button first and in the primary colour, a mis-click looked exactly like the
 * bug where an install finished but the admin panel was unreachable.
 */
class InstallCompleteButtonsTest extends TestCase
{
    public function test_the_admin_panel_button_comes_first(): void
    {
        $view = $this->completeView();

        $admin = strpos($view, 'value="{{ $adminLoginUrl }}"');
        $site = strpos($view, 'value="{{ $appUrl }}"');

        $this->assertNotFalse($admin, 'The admin panel button is missing.');
        $this->assertNotFalse($site, 'The view-the-site button is missing.');
        $this->assertLessThan($site, $admin, 'The admin panel is where the operator needs to go next.');
    }

    public function test_the_admin_panel_button_carries_the_primary_colour(): void
    {
        $view = $this->completeView();

        $adminForm = substr($view, (int) strpos($view, 'value="{{ $adminLoginUrl }}"'), 400);
        $siteForm = substr($view, (int) strpos($view, 'value="{{ $appUrl }}"'), 400);

        $this->assertStringContainsString('bg-blue-600', $adminForm);
        $this->assertStringNotContainsString('bg-blue-600', $siteForm);
    }

    public function test_each_button_names_its_destination(): void
    {
        $view = $this->completeView();

        // The URLs are printed elsewhere on the page too; what matters is
        // that each button block carries its own.
        $buttons = substr($view, (int) strpos($view, 'value="{{ $adminLoginUrl }}"'));

        $this->assertStringContainsString('{{ $adminLoginUrl }}</p>', $buttons);
        $this->assertStringContainsString('{{ $appUrl }}</p>', $buttons);
    }

    private function completeView(): string
    {
        return (string) file_get_contents(resource_path('views/install/complete.blade.php'));
    }
}
