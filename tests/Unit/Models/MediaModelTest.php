<?php

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
