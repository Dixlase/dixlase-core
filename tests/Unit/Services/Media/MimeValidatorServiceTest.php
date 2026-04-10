<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\MimeValidatorService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MimeValidatorServiceTest extends TestCase
{
    private MimeValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MimeValidatorService();
    }

    public function test_validate_valid_jpeg(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $result = $this->service->validate($file);

        $this->assertTrue($result->isValid());
    }

    public function test_validate_valid_png(): void
    {
        $file = UploadedFile::fake()->image('test.png', 100, 100);

        $result = $this->service->validate($file);

        $this->assertTrue($result->isValid());
    }

    public function test_get_mime_type_for_known_extension(): void
    {
        $mime = $this->service->getMimeTypeForExtension('jpg');

        $this->assertNotNull($mime);
        $this->assertStringContainsString('image', $mime);
    }

    public function test_get_mime_type_for_unknown_extension(): void
    {
        $mime = $this->service->getMimeTypeForExtension('xyz123');

        $this->assertNull($mime);
    }

    public function test_get_mime_type_for_pdf(): void
    {
        $mime = $this->service->getMimeTypeForExtension('pdf');

        $this->assertNotNull($mime);
        $this->assertEquals('application/pdf', $mime);
    }

    public function test_validate_result_has_extension(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 50, 50);

        $result = $this->service->validate($file);

        $this->assertEquals('jpg', $result->getExtension());
    }

    public function test_validate_result_to_array(): void
    {
        $file = UploadedFile::fake()->image('test.png', 50, 50);

        $result = $this->service->validate($file);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertArrayHasKey('warnings', $array);
    }
}
