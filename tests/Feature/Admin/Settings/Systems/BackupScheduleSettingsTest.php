<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Admin\Settings\Systems;

use App\DTO\Backup\BackupResultDTO;
use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Services\Backup\BackupSchedule;
use App\Services\Backup\BackupScheduleMonitor;
use App\Services\SystemNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\Concerns\BypassesRecentAuthentication;
use Tests\TestCase;

/**
 * #635: the scheduled backup is changed or turned off on the backup
 * settings screen, and a failed scheduled backup shows a banner to the
 * members who manage backups.
 */
class BackupScheduleSettingsTest extends TestCase
{
    use BypassesRecentAuthentication;
    use RefreshDatabase;

    private string $stateFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        // The admin view namespace is registered at boot only on an installed site
        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);
        $this->withoutVite();

        $this->stateFile = storage_path('framework/testing/backup-schedule-'.uniqid().'.json');
        config(['backup.schedule.state_file' => $this->stateFile]);
    }

    protected function tearDown(): void
    {
        @unlink($this->stateFile);
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function member(MemberRole $role): Member
    {
        return Member::factory()->create([
            'account_name' => strtolower($role->name).'x',
            'email' => strtolower($role->name).'@example.com',
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'default_targets' => ['database'],
            'default_retention_days' => '',
            'schedule_enabled' => '1',
            'schedule_frequency' => 'daily',
            'schedule_targets' => ['database'],
        ], $overrides);
    }

    public function test_the_screen_shows_the_defaults(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->get(route('admin.settings.systems.backup.settings'))
            ->assertOk()
            ->assertSee(__('admin/settings/systems/backup/settings.schedule.label'))
            ->assertSee('name="schedule_targets[]"', false)
            ->assertSee(__('admin/settings/systems/backup/settings.schedule.enabled_help', ['time' => '02:30', 'days' => 14]));
    }

    public function test_the_schedule_can_be_changed_and_turned_off(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->post(route('admin.settings.systems.backup.settings.update'), $this->form([
                'schedule_frequency' => 'weekly',
                'schedule_targets' => ['database', 'media'],
            ]))
            ->assertRedirect(route('admin.settings.systems.backup.settings'));

        $schedule = app(BackupSchedule::class);
        $this->assertTrue($schedule->enabled());
        $this->assertSame('weekly', $schedule->frequency());
        $this->assertSame(['database', 'media'], $schedule->targets());

        // An unchecked toggle is absent from the request
        $form = $this->form();
        unset($form['schedule_enabled']);
        $this->post(route('admin.settings.systems.backup.settings.update'), $form)->assertRedirect();

        $this->assertSame('0', SiteSetting::getValue(BackupSchedule::SETTING_ENABLED));
        $this->assertFalse($schedule->enabled());
    }

    public function test_invalid_schedule_input_is_refused(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->from(route('admin.settings.systems.backup.settings'))
            ->post(route('admin.settings.systems.backup.settings.update'), $this->form([
                'schedule_frequency' => 'hourly',
                'schedule_targets' => [],
            ]))
            ->assertSessionHasErrors(['schedule_frequency', 'schedule_targets']);

        $this->assertNull(SiteSetting::getValue(BackupSchedule::SETTING_FREQUENCY));
    }

    public function test_a_failed_scheduled_backup_shows_a_banner_to_backup_managers(): void
    {
        $this->app->instance(SystemNotificationService::class, Mockery::mock(SystemNotificationService::class, [
            'sendAdminNotification' => true,
        ]));
        $monitor = app(BackupScheduleMonitor::class);

        $this->assertSame([], $monitor->banners());

        $monitor->recordScheduledRun(BackupResultDTO::failure('disk full'));
        $this->assertSame([], $monitor->banners(), 'nobody signed in');

        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');
        $banners = $monitor->banners();
        $this->assertCount(1, $banners);
        $this->assertSame('warning', $banners[0]['level']);
        $this->assertStringContainsString('disk full', $banners[0]['message']);
        $this->assertSame(route('admin.settings.systems.backup.index'), $banners[0]['actions'][0]['url']);

        $monitor->recordScheduledRun(BackupResultDTO::success(1, '/tmp/b.zip', 1, 0.1, ['database']));
        $this->assertSame([], $monitor->banners());
    }
}
