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

namespace Tests\Unit\Services\Media;

use App\Services\Media\SvgSanitizerService;
use Tests\TestCase;

/**
 * The sanitiser must preserve `<style>` elements when they only carry SVG
 * presentation properties (fill, stroke, opacity, …) so that Illustrator-style
 * class-based colouring survives upload. Anything outside that safe surface —
 * script schemes, at-rules, expression(), external url() — must still be
 * stripped or reject the whole element.
 */
class SvgSanitizerServiceTest extends TestCase
{
    private SvgSanitizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SvgSanitizerService;
    }

    private function wrap(string $inner): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'.$inner.'</svg>';
    }

    public function test_illustrator_class_based_fill_is_preserved(): void
    {
        $svg = $this->wrap(
            '<defs><style>.st0 { fill: #fff; }</style></defs>'.
            '<path class="st0" d="M0,0h10v10H0z"/>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringContainsString('<style', $out);
        $this->assertStringContainsString('.st0', $out);
        $this->assertStringContainsString('fill: #fff', $out);
    }

    public function test_multiple_safe_declarations_survive(): void
    {
        $svg = $this->wrap(
            '<defs><style>.a { fill: red; stroke: blue; stroke-width: 2; opacity: 0.5; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringContainsString('fill: red', $out);
        $this->assertStringContainsString('stroke: blue', $out);
        $this->assertStringContainsString('stroke-width: 2', $out);
        $this->assertStringContainsString('opacity: 0.5', $out);
    }

    public function test_unknown_properties_are_stripped(): void
    {
        $svg = $this->wrap(
            '<defs><style>.a { fill: red; background: url(evil.png); position: absolute; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringContainsString('fill: red', $out);
        $this->assertStringNotContainsString('background', $out);
        $this->assertStringNotContainsString('position', $out);
    }

    public function test_at_import_is_dropped(): void
    {
        $svg = $this->wrap(
            '<defs><style>@import url("https://evil.example.com/x.css"); .a { fill: red; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('@import', $out);
        $this->assertStringNotContainsString('evil.example.com', $out);
        $this->assertStringContainsString('fill: red', $out);
    }

    public function test_at_font_face_is_dropped(): void
    {
        $svg = $this->wrap(
            '<defs><style>'.
            '@font-face { font-family: "X"; src: url("https://evil.example.com/x.woff"); }'.
            '.a { fill: red; }'.
            '</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('@font-face', $out);
        $this->assertStringNotContainsString('evil.example.com', $out);
        $this->assertStringContainsString('fill: red', $out);
    }

    public function test_expression_call_is_dropped(): void
    {
        $svg = $this->wrap(
            '<defs><style>.a { fill: expression(alert(1)); }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('expression', $out);
        // The declaration is dropped; the resulting <style> is empty and the
        // element itself must be removed to avoid an empty stub.
        $this->assertStringNotContainsString('<style', $out);
    }

    public function test_javascript_scheme_is_dropped(): void
    {
        $svg = $this->wrap(
            '<defs><style>.a { fill: url("javascript:alert(1)"); }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('javascript', $out);
        $this->assertStringNotContainsString('<style', $out);
    }

    public function test_local_url_fragment_reference_is_kept(): void
    {
        // fill: url(#gradient1) is idiomatic — used to reference in-document
        // gradients / masks / patterns. Must survive.
        $svg = $this->wrap(
            '<defs>'.
            '<linearGradient id="g1"><stop offset="0" stop-color="red"/></linearGradient>'.
            '<style>.a { fill: url(#g1); }</style>'.
            '</defs>'.
            '<path class="a" d="M0,0h10v10H0z"/>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringContainsString('url(#g1)', $out);
    }

    public function test_external_url_reference_is_dropped(): void
    {
        $svg = $this->wrap(
            '<defs><style>.a { fill: url("https://evil.example.com/x.png"); }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('evil.example.com', $out);
        $this->assertStringNotContainsString('<style', $out);
    }

    public function test_selector_containing_angle_brackets_is_rejected(): void
    {
        // Malformed selector that could smuggle markup back into the CSS text.
        $svg = $this->wrap(
            '<defs><style>a</style><script>alert(1)</script> { fill: red; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert', $out);
    }

    public function test_event_handler_on_style_element_is_removed(): void
    {
        $svg = $this->wrap(
            '<defs><style onload="alert(1)">.a { fill: red; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('onload', $out);
        $this->assertStringNotContainsString('alert', $out);
        $this->assertStringContainsString('fill: red', $out);
    }

    public function test_is_safe_returns_true_for_clean_style(): void
    {
        $svg = $this->wrap('<defs><style>.st0 { fill: #fff; }</style></defs>');

        $this->assertTrue($this->service->isSafe($svg));
    }

    public function test_is_safe_returns_false_for_style_with_at_import(): void
    {
        $svg = $this->wrap('<defs><style>@import url("x.css"); .a { fill: red; }</style></defs>');

        $this->assertFalse($this->service->isSafe($svg));
    }

    public function test_is_safe_returns_false_for_style_with_expression(): void
    {
        $svg = $this->wrap('<defs><style>.a { fill: expression(alert(1)); }</style></defs>');

        $this->assertFalse($this->service->isSafe($svg));
    }

    public function test_css_comment_cannot_hide_at_rule(): void
    {
        // A parser that only strips the outer @-rule but doesn't handle comments
        // could be tricked. Ensure the comment is peeled first.
        $svg = $this->wrap(
            '<defs><style>/* @keep */ @import url("x.css"); .a { fill: red; }</style></defs>'
        );

        $out = $this->service->sanitize($svg);

        $this->assertStringNotContainsString('@import', $out);
        $this->assertStringNotContainsString('@keep', $out);
    }
}
