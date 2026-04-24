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

declare(strict_types=1);

namespace Tests\Feature\Admin\Front;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Models\Member;
use App\Services\FrontPageRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * フロントページ リビジョン管理画面のフィーチャーテスト
 *
 * リビジョン一覧表示、差分表示、復元動作、未認証アクセス制御を検証する。
 */
class AdminFrontRevisionsTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private FrontPage $frontPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->admin = Member::create([
            'account_name' => 'revadmin',
            'display_name' => 'Revision Admin',
            'email' => 'rev@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $this->frontPage = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => 'en',
            'title' => 'Hello',
            'content' => 'initial',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_index_lists_revisions(): void
    {
        app(FrontPageRevisionService::class)->record($this->frontPage);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.revisions.index'));

        $response->assertOk();
        $response->assertViewHas('revisions');
    }

    public function test_show_displays_diff_for_specific_revision(): void
    {
        $rev = app(FrontPageRevisionService::class)->record($this->frontPage);
        $this->frontPage->update(['content' => 'changed']);

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.revisions.show', $rev->id));

        $response->assertOk();
        $response->assertViewHas('revision');
        $response->assertViewHas('hasChanges', true);
    }

    public function test_restore_applies_revision_and_redirects(): void
    {
        $rev = app(FrontPageRevisionService::class)->record($this->frontPage);
        $this->frontPage->update(['content' => 'modified']);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.revisions.restore', $rev->id));

        $response->assertRedirect(route('admin.front.revisions.index'));
        $this->assertSame('initial', $this->frontPage->fresh()->content);
        $this->assertTrue(
            FrontPageRevision::where('front_page_id', $this->frontPage->id)
                ->where('type', FrontPageRevision::TYPE_RESTORE_BACKUP)
                ->exists()
        );
    }

    public function test_guest_cannot_access_revisions(): void
    {
        $response = $this->get(route('admin.front.revisions.index'));
        $response->assertRedirect();
    }
}
