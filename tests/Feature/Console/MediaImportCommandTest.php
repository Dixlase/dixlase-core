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

namespace Tests\Feature\Console;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\MediaSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `dls:media:import` registers local files in the media library (#902)
 */
class MediaImportCommandTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $sourceDir;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        config()->set('app.installed', true);

        Storage::fake(config('admin.files.storageDisk', 'public'));

        MediaSetting::updateOrCreate(['name' => 'allowed_file_types'], ['value' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'pdf'])]);
        MediaSetting::updateOrCreate(['name' => 'max_file_size'], ['value' => '10240']);

        $this->admin = $this->member('admin', MemberRole::SUPER_ADMIN);

        $this->sourceDir = storage_path('framework/testing/media-import-'.uniqid());
        File::ensureDirectoryExists($this->sourceDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceDir);

        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_it_imports_an_image_under_the_member_named(): void
    {
        $path = $this->image('cover.png', 640, 360);

        $this->artisan('dls:media:import', ['paths' => [$path], '--as' => 'admin@example.com'])
            ->assertSuccessful();

        $media = Media::sole();
        $this->assertSame('cover.png', $media->name);
        $this->assertSame('image/png', $media->type);
        $this->assertSame(640, (int) $media->width);
        $this->assertSame(360, (int) $media->height);
        $this->assertSame($this->admin->id, (int) $media->uploaded_by);
        Storage::disk(config('admin.files.storageDisk', 'public'))
            ->assertExists(config('admin.files.mediaPath', 'media').'/'.$media->path);

        $log = AuditLog::where('action', 'media.uploaded')->sole();
        $this->assertSame($this->admin->id, (int) $log->actor_id);
    }

    public function test_json_output_gives_each_files_id_and_path(): void
    {
        $first = $this->image('one.png');
        $second = $this->image('two.jpg', 20, 10);

        Artisan::call('dls:media:import', ['paths' => [$first, $second], '--as' => (string) $this->admin->id, '--json' => true]);
        $results = json_decode(Artisan::output(), true);

        $this->assertCount(2, $results);
        $this->assertSame(Media::where('name', 'one.png')->value('id'), $results[0]['id']);
        $this->assertStringStartsWith('/storage/'.config('admin.files.mediaPath', 'media').'/', $results[0]['path']);
        $this->assertStringEndsWith($results[0]['path'], $results[0]['url']);
        $this->assertFalse($results[0]['reused']);
        $this->assertSame('two.jpg', $results[1]['name']);
    }

    public function test_the_same_file_twice_gives_one_media_item(): void
    {
        $path = $this->image('photo.png');
        $copy = $this->sourceDir.'/photo-copy.png';
        File::copy($path, $copy);

        $this->artisan('dls:media:import', ['paths' => [$path], '--as' => 'admin@example.com'])->assertSuccessful();

        Artisan::call('dls:media:import', ['paths' => [$copy], '--as' => 'admin@example.com', '--json' => true]);
        $results = json_decode(Artisan::output(), true);

        $this->assertSame(1, Media::count());
        $this->assertTrue($results[0]['reused']);
        $this->assertSame(Media::sole()->id, $results[0]['id']);
    }

    public function test_a_type_the_upload_form_refuses_is_refused_with_its_message(): void
    {
        $path = $this->sourceDir.'/notes.txt';
        File::put($path, 'plain text');

        $this->artisan('dls:media:import', ['paths' => [$path], '--as' => 'admin@example.com'])
            ->expectsOutputToContain(__('http/requests/admin/media/admin_media_store_request.allowed_file_types_are'))
            ->assertFailed();

        $this->assertSame(0, Media::count());
    }

    public function test_a_file_over_the_size_limit_is_refused(): void
    {
        MediaSetting::updateOrCreate(['name' => 'max_file_size'], ['value' => '1']);
        $path = $this->image('large.png', 800, 800, 50);

        $this->artisan('dls:media:import', ['paths' => [$path], '--as' => 'admin@example.com'])
            ->assertFailed();

        $this->assertSame(0, Media::count());
    }

    public function test_one_failure_fails_the_run_but_the_other_files_are_imported(): void
    {
        $good = $this->image('good.png');

        $this->artisan('dls:media:import', ['paths' => [$good, $this->sourceDir.'/missing.png'], '--as' => 'admin@example.com'])
            ->expectsOutputToContain(__('admin/media/import.not_a_file', ['path' => $this->sourceDir.'/missing.png']))
            ->assertFailed();

        $this->assertSame(['good.png'], Media::pluck('name')->all());
    }

    public function test_it_needs_a_member(): void
    {
        $path = $this->image('a.png');

        $this->artisan('dls:media:import', ['paths' => [$path]])
            ->expectsOutputToContain(__('admin/media/import.member_required'))
            ->assertFailed();

        $this->artisan('dls:media:import', ['paths' => [$path], '--as' => 'nobody@example.com'])
            ->expectsOutputToContain(__('admin/media/import.member_not_found', ['identifier' => 'nobody@example.com']))
            ->assertFailed();

        $this->assertSame(0, Media::count());
    }

    public function test_an_inactive_member_is_refused(): void
    {
        $this->member('former', MemberRole::SUPER_ADMIN, MemberStatus::Inactive);

        $this->artisan('dls:media:import', ['paths' => [$this->image('a.png')], '--as' => 'former@example.com'])
            ->expectsOutputToContain(__('admin/media/import.member_inactive', ['identifier' => 'former@example.com']))
            ->assertFailed();

        $this->assertSame(0, Media::count());
    }

    public function test_a_member_who_may_not_upload_is_refused(): void
    {
        $this->member('guest', MemberRole::GUEST);

        $this->artisan('dls:media:import', ['paths' => [$this->image('a.png')], '--as' => 'guest@example.com'])
            ->expectsOutputToContain(__('admin/media/import.not_allowed', ['member' => 'guest@example.com']))
            ->assertFailed();

        $this->assertSame(0, Media::count());
    }

    private function image(string $name, int $width = 10, int $height = 10, int $kilobytes = 0): string
    {
        $fake = UploadedFile::fake()->image($name, $width, $height);
        $path = $this->sourceDir.'/'.$name;
        File::copy($fake->getPathname(), $path);

        if ($kilobytes > 0 && filesize($path) < $kilobytes * 1024) {
            // Pad past the limit; trailing bytes after IEND leave the PNG readable
            File::append($path, str_repeat("\0", $kilobytes * 1024));
        }

        return $path;
    }

    private function member(string $name, MemberRole $role, MemberStatus $status = MemberStatus::Active): Member
    {
        return Member::create([
            'account_name' => $name,
            'display_name' => ucfirst($name),
            'email' => $name.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => $status,
        ]);
    }
}
