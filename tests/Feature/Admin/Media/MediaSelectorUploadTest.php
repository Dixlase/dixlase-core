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
use App\Models\MediaSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The upload inside the media selector modal (x-media.selector). The modal
 * posts one file at a time to admin.media.store and selects the id that
 * comes back, so the component has to offer the upload only to members the
 * route would accept, and the route has to answer a single file with its id.
 */
class MediaSelectorUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        MediaSetting::updateOrCreate(
            ['name' => 'allowed_file_types'],
            ['value' => json_encode(['jpg', 'png'])]
        );
        MediaSetting::updateOrCreate(
            ['name' => 'max_file_size'],
            ['value' => '10240']
        );
        Cache::flush();

        Storage::fake(config('admin.files.storageDisk', 'public'));
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_a_member_who_can_edit_media_gets_the_upload(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');

        $view = $this->blade('<x-media.selector id="testSelector" />');

        $view->assertSee('x-data="mediaSelectorUpload"', false);
        $view->assertSee('data-upload-url="'.route('admin.media.store').'"', false);
        $view->assertSee('id="testSelector-upload"', false);
    }

    public function test_a_member_who_cannot_edit_media_gets_no_upload_at_all(): void
    {
        $this->actingAs($this->member(MemberRole::GUEST), 'member');

        $view = $this->blade('<x-media.selector id="testSelector" />');

        $view->assertDontSee('mediaSelectorUpload', false);
        $view->assertDontSee('testSelector-upload', false);
        $view->assertDontSee('href="'.route('admin.media.upload').'"', false);
    }

    public function test_the_file_picker_accepts_the_types_the_media_settings_allow(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');

        $view = $this->blade('<x-media.selector id="testSelector" />');

        $view->assertSee('accept=".jpg,.png"', false);
    }

    /**
     * Callers (the OnePage theme among them) still pass the prop. It is
     * accepted, and it no longer leads away from the page.
     */
    public function test_confirm_upload_navigation_is_accepted_and_no_longer_navigates(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');

        $view = $this->blade('<x-media.selector id="testSelector" :confirmUploadNavigation="true" />');

        $view->assertSee('x-data="mediaSelectorUpload"', false);
        $view->assertDontSee('testSelector_uploadConfirmModal', false);
        $view->assertDontSee('href="'.route('admin.media.upload').'"', false);
    }

    public function test_a_single_file_upload_answers_with_the_id_the_selector_selects(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        $response = $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->postJson(route('admin.media.store'), [
                'file' => UploadedFile::fake()->image('card-ja.png', 1200, 630),
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('results.0.success', true)
            ->assertJsonPath('results.0.name', 'card-ja.png');

        $this->assertSame(Media::firstOrFail()->id, $response->json('results.0.id'));
    }

    /**
     * A refused file is answered with a reason the modal can print next to
     * the file, not with a redirect.
     */
    public function test_a_refused_file_answers_with_a_reason(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        $response = $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->postJson(route('admin.media.store'), [
                'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422);

        // The admin JSON error envelope; selector-upload.js reads this path
        $this->assertNotEmpty($response->json('error.details.file.0'));
        $this->assertSame(0, Media::count());
    }

    private function member(MemberRole $role): Member
    {
        return Member::create([
            'account_name' => 'member-'.$role->value,
            'display_name' => 'Member '.$role->value,
            'email' => 'member-'.$role->value.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
        ]);
    }
}
