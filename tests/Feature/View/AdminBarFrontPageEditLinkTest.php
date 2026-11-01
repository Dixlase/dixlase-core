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
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The admin bar's edit link for the site's own front page.
 *
 * Every other front page sets `admin_bar_edit_url` from its own view, but
 * core's front page is rendered by the active theme, so the component
 * offers that one link itself. These tests pin when it does: the route
 * being the front page (under a locale prefix too), the member being
 * allowed to open the editor, and a view's own sections still winning.
 */
class AdminBarFrontPageEditLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_who_may_edit_the_front_page_is_offered_the_link(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('welcome');

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('data-admin-bar-edit', $html);
        $this->assertStringContainsString('href="'.route('admin.front.edit').'"', $html);
        $this->assertStringContainsString(__('components/ui-admin-bar.edit_front_page'), $html);
    }

    public function test_a_member_who_may_not_edit_the_front_page_is_offered_nothing(): void
    {
        $this->signIn(MemberRole::CONTRIBUTOR);
        $this->onRoute('welcome');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    public function test_the_locale_prefixed_front_page_is_offered_the_link_too(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('welcome.localized');

        $this->assertStringContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    public function test_another_page_is_offered_nothing(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('dixlase-blog::front.show');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    public function test_the_admin_layout_is_offered_nothing(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('welcome');

        $this->assertStringNotContainsString(
            'data-admin-bar-edit',
            Blade::render('<x-ui-admin-bar :isAdminLayout="true" />')
        );
    }

    public function test_a_view_that_sets_the_sections_itself_still_wins(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('welcome');

        View::startSection('admin_bar_edit_url', '/admin-custom/pages/3/edit');
        View::startSection('admin_bar_edit_label', 'Edit this page');

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('href="/admin-custom/pages/3/edit"', $html);
        $this->assertStringNotContainsString(route('admin.front.edit'), $html);
        $this->assertStringNotContainsString(__('components/ui-admin-bar.edit_front_page'), $html);
    }

    public function test_the_design_button_is_gone(): void
    {
        $this->signIn(MemberRole::EDITOR);
        $this->onRoute('welcome');

        $this->assertStringNotContainsString('fa-paint-brush', Blade::render('<x-ui-admin-bar />'));
    }

    public function test_a_visitor_is_offered_nothing(): void
    {
        $this->onRoute('welcome');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    /**
     * Sign a member of the given role in to the admin, as the component
     * resolves it.
     */
    private function signIn(MemberRole $role): void
    {
        $this->actingAs(Member::factory()->state(new Sequence(['role' => $role]))->create(), 'member');
    }

    /**
     * Render as if the current request had matched the named route, which
     * is what `request()->routeIs()` reads.
     */
    private function onRoute(string $name): void
    {
        $route = (new Route('GET', '/', []))->name($name);

        $this->app['request']->setRouteResolver(fn () => $route);
    }
}
