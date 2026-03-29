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

namespace Tests\Unit\DTO\Editor;

use App\Contracts\Plugin\EditorCapableInterface;
use App\DTO\Editor\EditorInfo;
use Tests\TestCase;

class EditorInfoTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $info = new EditorInfo(
            typeSlug: 'gui',
            label: 'GUI Editor',
            description: 'A block editor',
            icon: 'fas fa-paint-brush',
            viewName: 'test::editor-panel',
            pluginDirectory: 'TestPlugin',
            pluginSlug: 'test-plugin',
            assets: ['js' => ['js/app.js'], 'css' => []],
            contentFormat: 'json',
        );

        $this->assertSame('gui', $info->typeSlug);
        $this->assertSame('GUI Editor', $info->label);
        $this->assertSame('A block editor', $info->description);
        $this->assertSame('fas fa-paint-brush', $info->icon);
        $this->assertSame('test::editor-panel', $info->viewName);
        $this->assertSame('TestPlugin', $info->pluginDirectory);
        $this->assertSame('test-plugin', $info->pluginSlug);
        $this->assertSame(['js' => ['js/app.js'], 'css' => []], $info->assets);
        $this->assertSame('json', $info->contentFormat);
    }

    public function test_json_serialize_returns_expected_structure(): void
    {
        $info = new EditorInfo(
            typeSlug: 'gui',
            label: 'GUI Editor',
            description: 'A block editor',
            icon: 'fas fa-paint-brush',
            viewName: 'test::editor-panel',
            pluginDirectory: 'TestPlugin',
            pluginSlug: 'test-plugin',
            assets: ['js' => ['js/app.js'], 'css' => []],
            contentFormat: 'json',
        );

        $json = $info->jsonSerialize();

        $this->assertSame('gui', $json['type_slug']);
        $this->assertSame('GUI Editor', $json['label']);
        $this->assertSame('test-plugin', $json['plugin_slug']);
        $this->assertSame('json', $json['content_format']);
        $this->assertArrayNotHasKey('view_name', $json);
        $this->assertArrayNotHasKey('assets', $json);
    }

    public function test_from_capability_creates_correct_instance(): void
    {
        $capability = $this->createMock(EditorCapableInterface::class);
        $capability->method('getEditorTypeSlug')->willReturn('gui');
        $capability->method('getEditorLabel')->willReturn(['en' => 'GUI Editor', 'ja' => 'GUIエディタ']);
        $capability->method('getEditorDescription')->willReturn(['en' => 'Block editor', 'ja' => 'ブロックエディタ']);
        $capability->method('getEditorIcon')->willReturn('fas fa-paint-brush');
        $capability->method('getEditorViewName')->willReturn('test::editor-panel');
        $capability->method('getPluginDirectoryName')->willReturn('TestPlugin');
        $capability->method('getPluginSlug')->willReturn('test-plugin');
        $capability->method('getEditorAssets')->willReturn(['js' => ['js/app.js'], 'css' => []]);
        $capability->method('getContentFormat')->willReturn('json');

        $info = EditorInfo::fromCapability($capability);

        $this->assertSame('gui', $info->typeSlug);
        $this->assertSame('test-plugin', $info->pluginSlug);
        $this->assertSame('json', $info->contentFormat);
    }
}
