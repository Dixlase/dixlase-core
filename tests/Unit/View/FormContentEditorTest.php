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

namespace Tests\Unit\View;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * <x-form-content-editor> is the one editor of the content screens. Its
 * Alpine side is the registered contentEditor component, configured by
 * data-config, and its optional parts appear only when asked for.
 */
class FormContentEditorTest extends TestCase
{
    public function test_it_names_its_component_and_carries_the_settings(): void
    {
        $html = Blade::render('<x-form-content-editor storageType="database" editorType="markdown" content="# Hi" />');

        $this->assertStringContainsString('x-data="contentEditor"', $html);
        $config = $this->config($html);
        $this->assertSame('markdown', $config['editorType']);
        $this->assertSame('# Hi', $config['content']);
        $this->assertFalse($config['preview']);
    }

    public function test_the_text_editors_share_one_field(): void
    {
        $html = Blade::render('<x-form-content-editor contentFieldName="body" />');

        $this->assertSame(1, substr_count($html, '<textarea id="body"'));
        $this->assertStringContainsString('name="body"', $html);
    }

    public function test_the_editor_cards_fill_the_row_and_follow_a_fixed_list(): void
    {
        $html = Blade::render('<x-form-content-editor :editors="[\'markdown\', \'html\']" />');

        $this->assertStringContainsString('repeat(auto-fit,minmax(12rem,1fr))', $html);
        $this->assertStringContainsString('value="markdown"', $html);
        $this->assertStringContainsString('value="html"', $html);
        $this->assertStringNotContainsString('value="blade"', $html);
        $this->assertStringNotContainsString('value="gui"', $html);
    }

    public function test_preview_and_asset_tabs_appear_only_when_asked_for(): void
    {
        $plain = Blade::render('<x-form-content-editor />');
        $this->assertStringNotContainsString('x-ref="previewIframe"', $plain);
        $this->assertStringNotContainsString('name="custom_css"', $plain);

        $full = Blade::render(
            '<x-form-content-editor :preview="true" previewFrameUrl="/frame" previewUrl="/render" :assetTabs="true" customCss="h1{}" />'
        );
        $this->assertStringContainsString('x-ref="previewIframe"', $full);
        $this->assertStringContainsString('name="custom_css"', $full);
        $this->assertStringContainsString('name="custom_js"', $full);
        $this->assertSame('/frame', $this->config($full)['previewFrameUrl']);
    }

    public function test_the_settings_survive_markup_in_the_content(): void
    {
        $content = '</textarea><script>alert(1)</script>"\'';

        $html = Blade::render('<x-form-content-editor :content="$content" />', ['content' => $content]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertSame($content, $this->config($html)['content']);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $html): array
    {
        $this->assertSame(1, preg_match('/data-config="([^"]*)"/', $html, $match));

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
    }
}
