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
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * <x-admin.save-button> marks the form it saves for the unsaved-changes
 * guard (resources/src/admin/js/unsaved-changes-guard.js).
 */
class SaveButtonUnsavedChangesGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('menuEditable', null);
        View::share('errors', new ViewErrorBag());
    }

    public function test_the_form_it_saves_is_marked(): void
    {
        $html = Blade::render('<x-admin.save-button form="post-form" />');

        $this->assertStringContainsString('data-unsaved-changes-form="post-form"', $html);
        $this->assertStringNotContainsString('data-unsaved-changes-dirty', $html);
    }

    public function test_nothing_is_marked_without_a_form_when_opted_out_or_view_only(): void
    {
        $this->assertStringNotContainsString('data-unsaved-changes-form', Blade::render('<x-admin.save-button />'));
        $this->assertStringNotContainsString('data-unsaved-changes-form', Blade::render('<x-admin.save-button form="post-form" :guard="false" />'));

        View::share('menuEditable', false);
        $this->assertStringNotContainsString('data-unsaved-changes-form', Blade::render('<x-admin.save-button form="post-form" />'));
    }

    public function test_a_page_back_with_validation_errors_starts_dirty(): void
    {
        View::share('errors', (new ViewErrorBag())->put('default', new MessageBag(['title' => 'Required'])));

        $html = Blade::render('<x-admin.save-button form="post-form" />');

        $this->assertStringContainsString('data-unsaved-changes-dirty="1"', $html);
    }
}
