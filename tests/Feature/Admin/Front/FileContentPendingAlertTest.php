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

namespace Tests\Feature\Admin\Front;

use App\Enums\ContentEditorType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\FrontPage;
use App\Models\Member;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\Content\FileContentPublisher;
use App\Services\Content\FileContentRegistry;
use App\Services\SchedulerHeartbeat;
use App\Services\Security\ScheduledSecurityCheckMonitor;
use App\Services\SystemNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Being told about file changes waiting to be published (#746)
 *
 * The dashboard lists the records with links to their edit screens, the
 * first recorded change raises a banner and one mail that clear once
 * everything is published, and the admin says when the scheduler is not
 * running, since changes are then found only when a page is read.
 */
class FileContentPendingAlertTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    private MockInterface $notifications;

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

        Storage::fake('local');

        $this->notifications = Mockery::mock(SystemNotificationService::class);
        $this->notifications->shouldReceive('sendAdminNotification')->andReturn(true)->byDefault();
        $this->notifications->shouldReceive('isNotificationEnabled')->andReturn(false)->byDefault();
        $this->app->instance(SystemNotificationService::class, $this->notifications);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_the_dashboard_lists_a_pending_record_with_a_link_to_its_edit_screen(): void
    {
        $this->schedulerRunning();
        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);

        $item = $this->decoratedHealthItem('file_content');

        $this->assertNotNull($item);
        $this->assertSame('warning', $item['status']);
        $this->assertSame(__('admin/dashboard.file_content_pending', ['count' => 1]), $item['description']);
        $this->assertCount(1, $item['records']);
        $this->assertSame(__('common.file_changes.front_page'), $item['records'][0]['label']);
        $this->assertSame(route('admin.front.edit'), $item['records'][0]['url']);
        $this->assertTrue($item['records'][0]['is_clickable']);
        $this->assertSame(0, $item['records_more']);
    }

    public function test_the_dashboard_renders_the_record_link(): void
    {
        $this->schedulerRunning();
        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);

        $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.front.edit').'"', false)
            ->assertSee(__('common.file_changes.front_page'));
    }

    public function test_nothing_is_listed_while_nothing_is_pending_and_the_scheduler_runs(): void
    {
        $this->schedulerRunning();
        $this->createFilePage();

        $this->assertNull($this->decoratedHealthItem('file_content'));
    }

    public function test_the_first_recorded_change_raises_the_alert_and_sends_one_mail(): void
    {
        $this->notifications->shouldReceive('sendAdminNotification')->once()->andReturn(true);

        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);
        // Further reads of the same change, and the daily scan, say nothing new.
        app(FileContentPublisher::class)->check($page->fresh());
        app(FileContentRegistry::class)->checkAll(app(FileContentPublisher::class));

        $alert = $this->monitor()->alerts()[ScheduledSecurityCheckMonitor::KEY_FILE_CONTENT] ?? null;
        $this->assertNotNull($alert);
        $this->assertSame(1, $alert['count']);
    }

    public function test_publishing_clears_the_alert_and_the_dashboard_item(): void
    {
        $this->schedulerRunning();
        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.publish-file'))
            ->assertRedirect(route('admin.front.edit'));

        $this->assertArrayNotHasKey(ScheduledSecurityCheckMonitor::KEY_FILE_CONTENT, $this->monitor()->alerts());
        $this->assertNull($this->decoratedHealthItem('file_content'));
    }

    public function test_putting_the_file_back_clears_the_alert(): void
    {
        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);
        Storage::disk('local')->put('core/front/content.html', '<h1>Published</h1>');

        app(FileContentPublisher::class)->check($page->fresh());

        $this->assertArrayNotHasKey(ScheduledSecurityCheckMonitor::KEY_FILE_CONTENT, $this->monitor()->alerts());
    }

    public function test_a_member_who_can_open_the_front_page_editor_sees_the_banner(): void
    {
        $page = $this->createFilePage();
        $this->changeFileOnDisk($page);

        $banner = collect($this->monitor()->banners())->firstWhere('icon', 'fas fa-file-pen');

        $this->assertNotNull($banner);
        $this->assertSame('warning', $banner['level']);
        $this->assertSame(route('admin.dashboard'), $banner['actions'][0]['url']);
    }

    public function test_the_dashboard_says_when_the_scheduler_is_not_running(): void
    {
        $page = $this->createFilePage();

        $idle = $this->decoratedHealthItem('file_content');
        $this->assertNotNull($idle, 'A site that stores files is told even when nothing is pending');
        $this->assertSame('recommendation', $idle['status']);
        $this->assertSame(__('admin/dashboard.file_content_scheduler_stopped'), $idle['description']);

        $this->changeFileOnDisk($page);

        $pending = $this->decoratedHealthItem('file_content');
        $this->assertStringContainsString(__('admin/dashboard.file_content_scheduler_stopped'), $pending['description']);
    }

    public function test_a_site_that_stores_nothing_in_files_is_not_told_about_the_scheduler(): void
    {
        $this->assertNull($this->decoratedHealthItem('file_content'));
    }

    public function test_the_front_page_settings_say_when_the_scheduler_is_not_running(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.settings'))
            ->assertOk()
            ->assertSee(__('common.file_changes.scheduler_stopped_title'));

        $this->schedulerRunning();

        $this->actingAs($this->admin, 'member')
            ->get(route('admin.front.settings'))
            ->assertOk()
            ->assertDontSee(__('common.file_changes.scheduler_stopped_title'));
    }

    private function createFilePage(): FrontPage
    {
        $this->actingAs($this->admin, 'member')->post(route('admin.front.store'), [
            'lang' => 'en',
            'editor_type' => ContentEditorType::HTML->slug(),
            'storage_type' => 'file',
            'content' => '<h1>Published</h1>',
        ])->assertRedirect(route('admin.front.edit'));

        return FrontPage::findByType('main_content');
    }

    /**
     * Change the body on disk and let a render find it, as a visitor would
     */
    private function changeFileOnDisk(FrontPage $page): void
    {
        Storage::disk('local')->put('core/front/content.html', '<h1>Edited on disk</h1>');
        app(FileContentPublisher::class)->check($page->fresh());
    }

    private function schedulerRunning(): void
    {
        app(SchedulerHeartbeat::class)->touch();
    }

    private function monitor(): ScheduledSecurityCheckMonitor
    {
        return $this->app->make(ScheduledSecurityCheckMonitor::class);
    }

    /**
     * One decorated site health item, as the dashboard renders it
     *
     * @return array<string, mixed>|null
     */
    private function decoratedHealthItem(string $key): ?array
    {
        $this->actingAs($this->admin, 'member');
        $items = DashboardPresenter::decorateSiteHealthItems(DashboardPresenter::siteHealth($this->admin), true);

        return collect($items)->firstWhere('key', $key);
    }
}
