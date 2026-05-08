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

namespace Tests\Unit\Services;

use App\Enums\ContentEditorType;
use App\Services\ContentPreviewService;
use Tests\TestCase;

class ContentPreviewServiceSecurityTest extends TestCase
{
    private ContentPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContentPreviewService::class);
    }

    public function test_render_from_slug_does_not_execute_blade_by_default(): void
    {
        $content = '{{ 7 * 6 }}';

        $rendered = $this->service->renderFromSlug($content, 'blade');

        $this->assertStringNotContainsString('42', $rendered);
        // The raw template tokens should pass through as HTML rather than being
        // evaluated by the Blade compiler.
        $this->assertSame('{{ 7 * 6 }}', $rendered);
    }

    public function test_render_from_slug_does_not_execute_php_blade_directives(): void
    {
        // If Blade::render were invoked, this would set the global and we'd
        // be able to read it back. The downgrade-to-HTML behaviour means the
        // directive should appear in the output as escaped text.
        $content = '@php $GLOBALS["dixlase_test_rce_marker"] = 1; @endphp';

        $rendered = $this->service->renderFromSlug($content, 'blade');

        $this->assertArrayNotHasKey('dixlase_test_rce_marker', $GLOBALS);
        $this->assertStringContainsString('@php', $rendered);
    }

    public function test_render_from_slug_executes_blade_only_with_explicit_opt_in(): void
    {
        $content = '{{ 7 * 6 }}';

        $rendered = $this->service->renderFromSlug($content, 'blade', allowExecutableTemplates: true);

        $this->assertSame('42', $rendered);
    }

    public function test_render_from_slug_html_is_unaffected_by_security_change(): void
    {
        $content = '<p>hello</p>';

        $rendered = $this->service->renderFromSlug($content, 'html');

        $this->assertSame('<p>hello</p>', $rendered);
    }

    public function test_render_with_typed_enum_still_supports_blade_for_trusted_callers(): void
    {
        $content = '{{ 1 + 2 }}';

        $rendered = $this->service->render($content, ContentEditorType::BLADE);

        $this->assertSame('3', $rendered);
    }

    public function test_render_from_slug_returns_empty_for_unknown_slug(): void
    {
        $rendered = $this->service->renderFromSlug('whatever', 'no-such-editor');

        $this->assertSame('', $rendered);
    }
}
