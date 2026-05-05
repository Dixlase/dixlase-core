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

use App\Contracts\PluginIntegration\BlockProviderInterface;
use App\DTO\PluginIntegration\BlockContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BlockContextTest extends TestCase
{
    public function test_can_create_with_minimum_arguments(): void
    {
        $ctx = new BlockContext(surface: BlockProviderInterface::SURFACE_EDITOR);

        $this->assertSame('editor', $ctx->surface);
        $this->assertNull($ctx->areaName);
        $this->assertNull($ctx->siteId);
        $this->assertSame([], $ctx->meta);
    }

    public function test_can_create_with_all_arguments(): void
    {
        $ctx = new BlockContext(
            surface: BlockProviderInterface::SURFACE_WIDGET_AREA,
            areaName: 'sidebar',
            siteId: 7,
            meta: ['theme' => 'dixlase-onepage'],
        );

        $this->assertSame('widget_area', $ctx->surface);
        $this->assertSame('sidebar', $ctx->areaName);
        $this->assertSame(7, $ctx->siteId);
        $this->assertSame(['theme' => 'dixlase-onepage'], $ctx->meta);
    }

    public function test_surface_helpers(): void
    {
        $editor = new BlockContext(surface: BlockProviderInterface::SURFACE_EDITOR);
        $widget = new BlockContext(surface: BlockProviderInterface::SURFACE_WIDGET_AREA);
        $preview = new BlockContext(surface: BlockProviderInterface::SURFACE_PREVIEW);

        $this->assertTrue($editor->isEditor());
        $this->assertFalse($editor->isWidgetArea());
        $this->assertFalse($editor->isPreview());

        $this->assertTrue($widget->isWidgetArea());
        $this->assertTrue($preview->isPreview());
    }

    public function test_json_serialize_contains_all_keys(): void
    {
        $ctx = new BlockContext(
            surface: BlockProviderInterface::SURFACE_PREVIEW,
            areaName: null,
            siteId: 1,
            meta: ['foo' => 'bar'],
        );

        $this->assertSame(
            ['surface' => 'preview', 'area_name' => null, 'site_id' => 1, 'meta' => ['foo' => 'bar']],
            $ctx->jsonSerialize(),
        );
    }

    public function test_dto_is_readonly(): void
    {
        $reflection = new ReflectionClass(BlockContext::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_surface_constants_match_documented_strings(): void
    {
        $this->assertSame('editor', BlockProviderInterface::SURFACE_EDITOR);
        $this->assertSame('widget_area', BlockProviderInterface::SURFACE_WIDGET_AREA);
        $this->assertSame('preview', BlockProviderInterface::SURFACE_PREVIEW);
    }
}
