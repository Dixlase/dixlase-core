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
use App\Models\Media;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminMediaBulkDeleteTest extends TestCase
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

        SiteSetting::setValue('site_name', 'Test Site');

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

    private function makeMedia(string $name = 'sample.jpg'): Media
    {
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');
        Storage::disk($disk)->put("{$mediaPath}/{$name}", 'stub');

        return Media::create([
            'name' => $name,
            'path' => $name,
            'type' => 'image/jpeg',
            'file_size' => 4,
            'uploaded_by' => $this->admin->id,
        ]);
    }

    public function test_bulk_delete_removes_all_selected_files(): void
    {
        $a = $this->makeMedia('a.jpg');
        $b = $this->makeMedia('b.jpg');
        $c = $this->makeMedia('c.jpg');

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.bulk-delete'), [
                'ids' => [$a->id, $c->id],
            ]);

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('media', ['id' => $a->id]);
        $this->assertDatabaseMissing('media', ['id' => $c->id]);
        $this->assertDatabaseHas('media', ['id' => $b->id]);
    }

    public function test_bulk_delete_removes_files_from_storage(): void
    {
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');
        $m = $this->makeMedia('to-remove.jpg');

        Storage::disk($disk)->assertExists("{$mediaPath}/to-remove.jpg");

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.bulk-delete'), [
                'ids' => [$m->id],
            ]);

        Storage::disk($disk)->assertMissing("{$mediaPath}/to-remove.jpg");
    }

    public function test_bulk_delete_rejects_empty_ids(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.bulk-delete'), [
                'ids' => [],
            ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_rejects_missing_ids(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.bulk-delete'), []);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_ignores_unknown_ids_and_only_removes_matches(): void
    {
        $real = $this->makeMedia('real.jpg');
        $bogus = 999999;

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.media.bulk-delete'), [
                'ids' => [$real->id, $bogus],
            ]);

        $response->assertRedirect(route('admin.media.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('media', ['id' => $real->id]);
    }
}
