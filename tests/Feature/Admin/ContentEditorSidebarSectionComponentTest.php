<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * <x-content-editor.sidebar-section> opens and closes, shows its summary
 * while closed, and opens when one of its fields has an error.
 */
class ContentEditorSidebarSectionComponentTest extends TestCase
{
    public function test_a_closed_section_hides_its_body_and_shows_the_summary(): void
    {
        $html = $this->render(['title' => 'Classification', 'summary' => '1 category, 4 tags']);

        $this->assertStringContainsString('x-data="contentEditorSidebarSection"', $html);
        $this->assertStringContainsString('data-open="0"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertMatchesRegularExpression('/<span x-show="! open"\s+class="[^"]*">1 category, 4 tags<\/span>/', $html);
        $this->assertMatchesRegularExpression('/x-collapse\s+style="display: none"/', $html);
        $this->assertStringContainsString('Body', $html);
    }

    public function test_an_open_section_shows_its_body_and_hides_the_summary(): void
    {
        $html = $this->render(['title' => 'Publish', 'open' => true, 'summary' => 'Published']);

        $this->assertStringContainsString('data-open="1"', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertMatchesRegularExpression('/x-show="! open"\s+style="display: none"/', $html);
        $this->assertDoesNotMatchRegularExpression('/x-collapse\s+style="display: none"/', $html);
    }

    public function test_the_toggle_controls_the_panel(): void
    {
        $html = $this->render(['title' => 'SEO', 'icon' => 'fas fa-magnifying-glass']);

        $this->assertMatchesRegularExpression('/aria-controls="(sidebar-section-[A-Za-z0-9]+)"/', $html);
        preg_match('/aria-controls="([^"]+)"/', $html, $m);
        $this->assertStringContainsString('id="'.$m[1].'"', $html);
        $this->assertStringContainsString('<i class="fas fa-magnifying-glass', $html);
    }

    public function test_an_error_on_one_of_its_fields_opens_it(): void
    {
        $this->withErrors(['tags.2' => ['Too long.']]);

        $html = $this->render(['title' => 'Classification', 'errorFields' => ['categories', 'tags.*']]);

        $this->assertStringContainsString('data-open="1"', $html);
        $this->assertStringContainsString('data-force-open="1"', $html);
        $this->assertStringContainsString('fa-circle-exclamation', $html);
    }

    public function test_an_error_elsewhere_leaves_it_closed(): void
    {
        $this->withErrors(['title' => ['Required.']]);

        $html = $this->render(['title' => 'Classification', 'errorFields' => ['categories', 'tags.*']]);

        $this->assertStringContainsString('data-open="0"', $html);
        $this->assertStringContainsString('data-force-open="0"', $html);
    }

    public function test_the_remember_key_is_passed_only_when_given(): void
    {
        $this->assertStringNotContainsString('data-remember-key', $this->render(['title' => 'A']));
        $this->assertStringContainsString(
            'data-remember-key="blog.post.seo"',
            $this->render(['title' => 'A', 'rememberKey' => 'blog.post.seo']),
        );
    }

    public function test_the_divider_can_be_left_out(): void
    {
        $this->assertStringContainsString('border-t', $this->render(['title' => 'A']));
        $this->assertStringNotContainsString('border-t', $this->render(['title' => 'A', 'divider' => false]));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function render(array $props): string
    {
        return Blade::render(
            '<x-content-editor.sidebar-section :title="$title" :icon="$icon" :open="$open" :summary="$summary" :errorFields="$errorFields" :rememberKey="$rememberKey" :divider="$divider">Body</x-content-editor.sidebar-section>',
            $props + [
                'icon' => null,
                'open' => false,
                'summary' => null,
                'errorFields' => [],
                'rememberKey' => null,
                'divider' => true,
            ],
        );
    }

    /**
     * @param  array<string, list<string>>  $messages
     */
    private function withErrors(array $messages): void
    {
        view()->share('errors', (new ViewErrorBag())->put('default', new MessageBag($messages)));
    }
}
