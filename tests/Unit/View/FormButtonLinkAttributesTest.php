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
 * A link rendered by the button component takes the same attributes as a
 * button. A link that cannot carry target, rel or data-* gets written by hand
 * instead, which is how hand-styled links end up next to the component's.
 */
class FormButtonLinkAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag());
    }

    public function test_a_link_forwards_the_attributes_it_is_given(): void
    {
        $html = Blade::render(
            '<x-form-button type="link" href="/docs" target="_blank" rel="noopener" title="Docs"'
            .' style="color: red" data-track="cta" aria-label="Open docs">Docs</x-form-button>'
        );

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
        $this->assertStringContainsString('title="Docs"', $html);
        $this->assertStringContainsString('style="color: red"', $html);
        $this->assertStringContainsString('data-track="cta"', $html);
        $this->assertStringContainsString('aria-label="Open docs"', $html);
    }

    public function test_a_link_binds_alpine_through_x_click(): void
    {
        $html = Blade::render('<x-form-button type="link" href="/docs" xClick="track()" label="Docs" />');

        $this->assertStringContainsString('@click="track()"', $html);
    }

    public function test_a_link_drops_an_onclick_attribute(): void
    {
        $html = Blade::render('<x-form-button type="link" href="/docs" onclick="doThing()" label="Docs" />');

        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_a_link_without_extra_attributes_carries_only_href_and_class(): void
    {
        $html = Blade::render('<x-form-button type="link" href="/docs" label="Docs" />');

        $this->assertSame(['class', 'href'], $this->openingTagAttributes($html, 'a'));
    }

    public function test_no_prop_reaches_the_markup_from_either_branch(): void
    {
        $props = 'variant="danger" size="sm" label="Go" icon="fas fa-save" iconPosition="right"'
            .' form="main" xDisabled="busy"';

        $link = Blade::render('<x-form-button type="link" href="/docs" '.$props.' />');
        $button = Blade::render('<x-form-button type="button" '.$props.' />');

        $this->assertSame(['class', 'href'], $this->openingTagAttributes($link, 'a'));
        $this->assertSame([':disabled', 'class', 'form', 'type'], $this->openingTagAttributes($button, 'button'));
    }

    /**
     * @return list<string> Attribute names on the first opening tag, sorted.
     */
    private function openingTagAttributes(string $html, string $tag): array
    {
        $this->assertSame(1, preg_match('/<'.$tag.'\b((?:[^>"]|"[^"]*")*)>/', $html, $opening));
        preg_match_all('/([^\s=]+)="[^"]*"/', $opening[1], $names);
        sort($names[1]);

        return $names[1];
    }
}
