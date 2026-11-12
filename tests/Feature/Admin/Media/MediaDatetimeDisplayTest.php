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

namespace Tests\Feature\Admin\Media;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Media;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The media screens printed the upload time as UTC, so a site whose display
 * timezone is not UTC showed some uploads under the previous day.
 */
class MediaDatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    private mixed $serverInstalled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        // ConfigHelper reads display_timezone from the database only when
        // INSTALLED is true, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $this->serverInstalled = $_SERVER['INSTALLED'] ?? null;
        $_SERVER['INSTALLED'] = 'true';

        // The service provider registers the admin view namespace only on an
        // installed site at boot, which a test is not.
        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();
    }

    protected function tearDown(): void
    {
        if ($this->previewFile !== null) {
            File::delete($this->previewFile);
        }
        SiteSetting::query()->whereIn('name', ['display_timezone', 'admin_mode'])->delete();
        AdminModeHelper::clearCache();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        // Laravel reads $_SERVER before $_ENV, and phpunit.xml leaves it
        // unset; leaving 'false' behind would override later tests that
        // set only $_ENV.
        if ($this->serverInstalled === null) {
            unset($_SERVER['INSTALLED']);
        } else {
            $_SERVER['INSTALLED'] = $this->serverInstalled;
        }

        parent::tearDown();
    }

    private function superAdmin(): Member
    {
        return Member::factory()->create([
            'account_name' => 'superadminx',
            'email' => 'superadmin@example.com',
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);
    }

    private ?string $previewFile = null;

    private function media(string $name = 'fixture.jpg'): Media
    {
        $media = Media::create([
            'name' => $name,
            'path' => $name,
            'type' => 'image/jpeg',
            'file_size' => 4,
        ]);
        DB::table('media')->where('id', $media->id)->update(['created_at' => '2026-03-01 15:30:45']);

        return $media;
    }

    public function test_the_media_card_shows_the_upload_date_in_the_display_timezone(): void
    {
        $this->media();

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('2026/03/02', false)
            ->assertDontSee('2026/03/01', false)
            ->assertSee('datetime="2026-03-01T15:30:45+00:00"', false);
    }

    public function test_the_media_preview_shows_the_upload_time_in_the_display_timezone(): void
    {
        // The preview answers 404 unless the file is on disk at the path the
        // controller builds, so a throwaway file is put there and removed in
        // tearDown().
        $name = 'datetime-test-'.uniqid().'.jpg';
        $this->previewFile = storage_path('app/'.config('admin.files.storageDisk').'/'.config('admin.files.mediaPath', 'media').'/'.$name);
        File::ensureDirectoryExists(dirname($this->previewFile));
        File::put($this->previewFile, 'stub');
        $media = $this->media($name);

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.media.preview', $media))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45', false)
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }
}
