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

namespace Tests\Feature\Admin\Media;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Media;
use App\Models\MediaSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * メディアアップロード フィーチャーテスト
 */
class AdminMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        BaseSetting::setValue('site_name', 'Test Site');

        // 許可するファイルタイプを設定
        MediaSetting::updateOrCreate(
            ['name' => 'allowed_file_types'],
            ['value' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'pdf'])]
        );
        MediaSetting::updateOrCreate(
            ['name' => 'max_file_size'],
            ['value' => '10240']
        );

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        Storage::fake(config('admin.files.storageDisk', 'public'));
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    /**
     * 単一ファイルのアップロードが成功することを検証
     */
    public function test_single_file_upload_stores_media_with_metadata(): void
    {
        $file = UploadedFile::fake()->image('test-photo.jpg', 640, 480)->size(256);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('success');

        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertEquals('test-photo.jpg', $media->name);
        $this->assertNotNull($media->file_size);
        $this->assertEquals($this->admin->id, $media->uploaded_by);
    }

    /**
     * 複数ファイルのAJAXアップロードが成功することを検証
     */
    public function test_multiple_files_ajax_upload_returns_json(): void
    {
        $file = UploadedFile::fake()->image('photo1.jpg', 800, 600)->size(128);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.media.store'), [
                'files' => [$file],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'results',
            'successCount',
            'failCount',
        ]);
        $response->assertJson(['successCount' => 1]);
    }

    /**
     * 画像ファイルのアップロード時にwidth/heightが保存されることを検証
     */
    public function test_image_upload_stores_dimensions(): void
    {
        $file = UploadedFile::fake()->image('dimension-test.png', 1920, 1080)->size(512);

        $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.media.store'), [
                'files' => [$file],
            ]);

        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertEquals(1920, $media->width);
        $this->assertEquals(1080, $media->height);
    }

    /**
     * ファイルサイズが保存されることを検証
     */
    public function test_upload_stores_file_size(): void
    {
        $file = UploadedFile::fake()->image('size-test.jpg', 100, 100)->size(64);

        $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.media.store'), [
                'files' => [$file],
            ]);

        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertNotNull($media->file_size);
        $this->assertGreaterThan(0, $media->file_size);
    }

    /**
     * ファイルなしのアップロードが失敗することを検証
     */
    public function test_upload_without_file_returns_error(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.media.store'), []);

        $response->assertStatus(422);
    }

    /**
     * Mediaモデルのformatted_file_sizeアクセサが正しく動作することを検証
     */
    public function test_media_formatted_file_size_accessor(): void
    {
        $media = new Media();

        $media->file_size = null;
        $this->assertNull($media->formatted_file_size);

        $media->file_size = 0;
        $this->assertEquals('0 B', $media->formatted_file_size);

        $media->file_size = 1024;
        $this->assertEquals('1 KB', $media->formatted_file_size);

        $media->file_size = 1048576;
        $this->assertEquals('1 MB', $media->formatted_file_size);

        $media->file_size = 1536;
        $this->assertEquals('1.5 KB', $media->formatted_file_size);
    }

    /**
     * Mediaモデルのformatted_dimensionsアクセサが正しく動作することを検証
     */
    public function test_media_formatted_dimensions_accessor(): void
    {
        $media = new Media();

        $media->width = null;
        $media->height = null;
        $this->assertNull($media->formatted_dimensions);

        $media->width = 1920;
        $media->height = 1080;
        $this->assertEquals('1920 × 1080 px', $media->formatted_dimensions);
    }

    /**
     * アップロード画面が正常に表示されることを検証
     */
    public function test_upload_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.upload'));

        $response->assertOk();
    }

    /**
     * メディア一覧画面が正常に表示されることを検証
     */
    public function test_index_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.index'));

        $response->assertOk();
    }
}
