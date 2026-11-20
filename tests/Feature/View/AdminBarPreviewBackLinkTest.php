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

namespace Tests\Feature\View;

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The admin bar's link on a preview tab (#1079).
 *
 * A preview view sets `admin_bar_edit_closes` next to its edit URL; the
 * link then reads "Back to the editor" and closes the tab (in the
 * browser, adminBar().backToEditor()). These tests pin the markup that
 * wires that up, and that an ordinary edit link is left as it was.
 */
class AdminBarPreviewBackLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_preview_offers_back_to_the_editor(): void
    {
        $this->signIn();
        View::startSection('admin_bar_edit_url', '/admin-custom/blog/3/edit');
        View::startSection('admin_bar_edit_closes', '1');

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('href="/admin-custom/blog/3/edit"', $html);
        $this->assertStringContainsString('data-admin-bar-edit-closes', $html);
        $this->assertStringContainsString('@click.prevent="backToEditor"', $html);
        $this->assertStringContainsString(__('components/ui-admin-bar.back_to_editor'), $html);
    }

    public function test_a_preview_keeps_a_label_the_view_gives(): void
    {
        $this->signIn();
        View::startSection('admin_bar_edit_url', '/admin-custom/blog/3/edit');
        View::startSection('admin_bar_edit_label', 'Back to the post');
        View::startSection('admin_bar_edit_closes', '1');

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('Back to the post', $html);
        $this->assertStringNotContainsString(__('components/ui-admin-bar.back_to_editor'), $html);
    }

    public function test_an_ordinary_edit_link_does_not_close_the_tab(): void
    {
        $this->signIn();
        View::startSection('admin_bar_edit_url', '/admin-custom/blog/3/edit');

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('data-admin-bar-edit', $html);
        $this->assertStringNotContainsString('data-admin-bar-edit-closes', $html);
        $this->assertStringNotContainsString('backToEditor', $html);
    }

    public function test_the_closing_flag_alone_offers_nothing(): void
    {
        $this->signIn();
        View::startSection('admin_bar_edit_closes', '1');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    private function signIn(): void
    {
        $this->actingAs(Member::factory()->state(new Sequence(['role' => MemberRole::EDITOR]))->create(), 'member');
    }
}
