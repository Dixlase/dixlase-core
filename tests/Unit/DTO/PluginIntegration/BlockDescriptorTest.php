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

namespace Tests\Unit\DTO\PluginIntegration;

use App\DTO\PluginIntegration\BlockDescriptor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BlockDescriptorTest extends TestCase
{
    public function test_can_create_with_minimum_arguments(): void
    {
        $desc = new BlockDescriptor(
            key: 'dixlase-blog.category_list',
            label: 'admin/blocks.category_list.label',
        );

        $this->assertSame('dixlase-blog.category_list', $desc->key);
        $this->assertSame('admin/blocks.category_list.label', $desc->label);
        $this->assertNull($desc->description);
        $this->assertNull($desc->icon);
        $this->assertSame([], $desc->configSchema);
        $this->assertSame([], $desc->defaultConfig);
        $this->assertSame([], $desc->usableIn);
        $this->assertSame('', $desc->source);
        $this->assertSame([], $desc->meta);
    }

    public function test_can_create_with_full_descriptor(): void
    {
        $desc = new BlockDescriptor(
            key: 'dixlase-blog.category_list',
            label: 'Category List',
            description: 'Lists blog categories with post counts',
            icon: 'heroicon-folder',
            configSchema: ['limit' => ['type' => 'integer']],
            defaultConfig: ['limit' => 10],
            usableIn: ['editor', 'widget_area'],
            source: 'dixlase-blog',
            meta: ['version' => '1.0'],
        );

        $this->assertSame('Category List', $desc->label);
        $this->assertSame('heroicon-folder', $desc->icon);
        $this->assertSame(['limit' => ['type' => 'integer']], $desc->configSchema);
        $this->assertSame(['limit' => 10], $desc->defaultConfig);
        $this->assertSame('dixlase-blog', $desc->source);
    }

    public function test_usable_in_helpers(): void
    {
        $editorOnly = new BlockDescriptor(key: 'a', label: 'A', usableIn: ['editor']);
        $widgetOnly = new BlockDescriptor(key: 'b', label: 'B', usableIn: ['widget_area']);
        $both = new BlockDescriptor(key: 'c', label: 'C', usableIn: ['editor', 'widget_area']);

        $this->assertTrue($editorOnly->isUsableInEditor());
        $this->assertFalse($editorOnly->isUsableInWidgetArea());

        $this->assertFalse($widgetOnly->isUsableInEditor());
        $this->assertTrue($widgetOnly->isUsableInWidgetArea());

        $this->assertTrue($both->isUsableInEditor());
        $this->assertTrue($both->isUsableInWidgetArea());
    }

    public function test_json_serialize_contains_all_keys(): void
    {
        $desc = new BlockDescriptor(
            key: 'dixlase-embeds.youtube',
            label: 'YouTube Embed',
            usableIn: ['editor', 'widget_area'],
            source: 'dixlase-embeds',
        );

        $payload = $desc->jsonSerialize();

        $this->assertSame('dixlase-embeds.youtube', $payload['key']);
        $this->assertSame(['editor', 'widget_area'], $payload['usable_in']);
        $this->assertSame('dixlase-embeds', $payload['source']);
        $this->assertArrayHasKey('config_schema', $payload);
        $this->assertArrayHasKey('default_config', $payload);
    }

    public function test_dto_is_readonly(): void
    {
        $reflection = new ReflectionClass(BlockDescriptor::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
