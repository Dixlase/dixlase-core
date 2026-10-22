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

namespace Tests\Unit\Database;

use App\Models\Media;
use Database\Factories\MediaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `Tests\TestCase` resolves a factory by checking the installed extensions'
 * namespaces first and then falling back to `Database\Factories\*`. These
 * cases exercise core's own factory directly rather than through
 * `Media::factory()`, because an installed extension's factory wins there --
 * so going through the model would test the extension's factory in a
 * development checkout and core's only in a bare one.
 */
class CoreMediaFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_ships_the_fallback_factory(): void
    {
        $this->assertTrue(
            class_exists(MediaFactory::class),
            'A core test calls Media::factory(), so the fallback the resolver '
            .'ends at has to exist in core -- a checkout with no extension '
            .'installed has nothing else to use.'
        );
    }

    public function test_it_creates_a_row_that_satisfies_the_not_null_columns(): void
    {
        $media = MediaFactory::new()->create();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertNotSame('', $media->name);
        $this->assertNotSame('', $media->path);
        $this->assertStringStartsWith('image/', $media->type);
        $this->assertNotNull($media->site_id, 'BelongsToSite fills this from SiteContext.');
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_the_uploader_is_a_member_by_default_and_can_be_dropped(): void
    {
        $this->assertNotNull(MediaFactory::new()->create()->uploaded_by);
        $this->assertNull(MediaFactory::new()->withoutUploader()->create()->uploaded_by);
    }

    public function test_the_document_state_is_not_an_image(): void
    {
        $media = MediaFactory::new()->document()->create();

        $this->assertSame('application/pdf', $media->type);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
    }

    public function test_each_row_gets_its_own_name(): void
    {
        $names = MediaFactory::new()->count(5)->create()->pluck('name');

        $this->assertCount(5, $names->unique(), 'A unique name keeps a list assertion stable.');
    }
}
