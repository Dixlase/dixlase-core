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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Saving a file's details goes through the shared save button (#777): a
 * confirmation modal submits the form, never a plain submit button.
 */
class MediaPreviewSaveConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private string $storageRoot;

    /**
     * What the three installed-flag sources held before this test.
     *
     * @var array<string, string|false|null>
     */
    private array $installedFlag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        // The flag is read as
        // `$_SERVER ?? $_ENV ?? env() ?? config('app.installed')`, so
        // $_SERVER decides and all three have to be set here -- see #926,
        // where a class that set only the last two passed or failed
        // depending on what an earlier test had left behind. Restored
        // rather than reset, so this class cannot decide a later test's
        // outcome either.
        $this->installedFlag = [
            'getenv' => getenv('INSTALLED'),
            'env' => array_key_exists('INSTALLED', $_ENV) ? (string) $_ENV['INSTALLED'] : null,
            'server' => array_key_exists('INSTALLED', $_SERVER) ? (string) $_SERVER['INSTALLED'] : null,
        ];

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = $this->member(MemberRole::SUPER_ADMIN);

        Storage::fake(config('admin.files.storageDisk', 'public'));

        // The preview checks the file under storage_path(); keep it out of
        // the working tree's storage
        $this->storageRoot = storage_path('framework/testing/media-preview-'.uniqid());
        $this->app->useStoragePath($this->storageRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        if ($this->installedFlag['getenv'] === false) {
            putenv('INSTALLED');
        } else {
            putenv('INSTALLED='.$this->installedFlag['getenv']);
        }

        foreach ([['env', &$_ENV], ['server', &$_SERVER]] as [$key, &$bag]) {
            if ($this->installedFlag[$key] === null) {
                unset($bag['INSTALLED']);
            } else {
                $bag['INSTALLED'] = $this->installedFlag[$key];
            }
        }

        parent::tearDown();
    }

    public function test_saving_a_file_asks_first_through_the_confirmation_modal(): void
    {
        $media = $this->makeMedia();

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.preview', $media))
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('id="mediaUpdateForm"', $html);
        $this->assertStringContainsString("openModal('confirmationModal')", $html);
        $this->assertStringContainsString('data-unsaved-changes-form="mediaUpdateForm"', $html);
        $this->assertMatchesRegularExpression('/id="confirmationModal".*form="mediaUpdateForm"/s', $html);

        // No button submits the details form directly
        $form = substr($html, strpos($html, 'id="mediaUpdateForm"'));
        $form = substr($form, 0, strpos($form, '</form>'));
        $this->assertStringNotContainsString('type="submit"', $form);
    }

    public function test_a_member_who_can_only_view_gets_a_disabled_save_button(): void
    {
        View::share('menuEditable', false);
        $media = $this->makeMedia();

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.media.preview', $media))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*save-button[^>]*disabled|<button[^>]*disabled[^>]*save-button/s',
            $response->getContent(),
        );
        $this->assertStringNotContainsString('data-unsaved-changes-form="mediaUpdateForm"', $response->getContent());
    }

    private function makeMedia(string $name = 'sample.png'): Media
    {
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');
        File::ensureDirectoryExists(storage_path("app/{$disk}/{$mediaPath}"));
        File::put(storage_path("app/{$disk}/{$mediaPath}/{$name}"), 'stub');

        return Media::create([
            'name' => $name,
            'path' => $name,
            'type' => 'image/png',
            'file_size' => 4,
            'uploaded_by' => $this->admin->id,
        ]);
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
