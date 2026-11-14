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

namespace Tests\Unit\DTO\Front;

use App\Contracts\Front\FrontPageBodyProviderInterface;
use App\DTO\Front\FrontPageBodyContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FrontPageBodyContextTest extends TestCase
{
    public function test_defaults_describe_the_live_front_page(): void
    {
        $ctx = new FrontPageBodyContext();

        $this->assertSame('front', $ctx->surface);
        $this->assertNull($ctx->locale);
        $this->assertSame(1, $ctx->page);
        $this->assertTrue($ctx->showHeading);
        $this->assertNull($ctx->siteId);
        $this->assertSame([], $ctx->meta);
    }

    public function test_can_create_with_all_arguments(): void
    {
        $ctx = new FrontPageBodyContext(
            surface: FrontPageBodyProviderInterface::SURFACE_PREVIEW,
            locale: 'ja',
            page: 3,
            showHeading: false,
            siteId: 7,
            meta: ['theme' => 'dixlase-onepage'],
        );

        $this->assertSame('preview', $ctx->surface);
        $this->assertSame('ja', $ctx->locale);
        $this->assertSame(3, $ctx->page);
        $this->assertFalse($ctx->showHeading);
        $this->assertSame(7, $ctx->siteId);
        $this->assertSame(['theme' => 'dixlase-onepage'], $ctx->meta);
    }

    public function test_surface_helpers(): void
    {
        $front = new FrontPageBodyContext(surface: FrontPageBodyProviderInterface::SURFACE_FRONT);
        $preview = new FrontPageBodyContext(surface: FrontPageBodyProviderInterface::SURFACE_PREVIEW);

        $this->assertTrue($front->isFront());
        $this->assertFalse($front->isPreview());

        $this->assertTrue($preview->isPreview());
        $this->assertFalse($preview->isFront());
    }

    public function test_json_serialize_contains_all_keys(): void
    {
        $ctx = new FrontPageBodyContext(
            surface: FrontPageBodyProviderInterface::SURFACE_PREVIEW,
            locale: 'en',
            page: 2,
            showHeading: false,
            siteId: 1,
            meta: ['foo' => 'bar'],
        );

        $this->assertSame([
            'surface' => 'preview',
            'locale' => 'en',
            'page' => 2,
            'show_heading' => false,
            'site_id' => 1,
            'meta' => ['foo' => 'bar'],
        ], $ctx->jsonSerialize());
    }

    public function test_dto_is_readonly(): void
    {
        $this->assertTrue((new ReflectionClass(FrontPageBodyContext::class))->isReadOnly());
    }

    public function test_surface_constants_match_documented_strings(): void
    {
        $this->assertSame('front', FrontPageBodyProviderInterface::SURFACE_FRONT);
        $this->assertSame('preview', FrontPageBodyProviderInterface::SURFACE_PREVIEW);
    }
}
