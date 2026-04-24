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

namespace Tests\Unit\Models;

use App\Models\Media;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    private function createMedia(array $overrides = []): Media
    {
        $member = Member::factory()->create();

        return Media::create(array_merge([
            'name' => 'test-image.jpg',
            'caption' => 'Test caption',
            'alt_text' => 'Test alt',
            'path' => 'media/test-image.jpg',
            'type' => 'image/jpeg',
            'file_size' => 1024000,
            'width' => 1920,
            'height' => 1080,
            'uploaded_by' => $member->id,
        ], $overrides));
    }

    public function test_media_can_be_created(): void
    {
        $media = $this->createMedia();

        $this->assertDatabaseHas('media', ['name' => 'test-image.jpg']);
    }

    public function test_member_relationship(): void
    {
        $media = $this->createMedia();

        $this->assertNotNull($media->member);
        $this->assertInstanceOf(Member::class, $media->member);
    }

    public function test_file_size_stored_as_integer(): void
    {
        $media = $this->createMedia(['file_size' => 2048000]);

        $this->assertEquals(2048000, $media->file_size);
    }

    public function test_dimensions_stored_correctly(): void
    {
        $media = $this->createMedia(['width' => 800, 'height' => 600]);

        $this->assertEquals(800, $media->width);
        $this->assertEquals(600, $media->height);
    }

    public function test_media_without_dimensions(): void
    {
        $media = $this->createMedia([
            'type' => 'application/pdf',
            'width' => null,
            'height' => null,
        ]);

        $this->assertNull($media->width);
        $this->assertNull($media->height);
    }
}
