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

namespace Tests\Feature\Console\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use App\Models\BackupRecord;
use App\Models\SiteSetting;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\Backup\BackupSchedule;
use App\Services\Backup\BackupScheduleMonitor;
use App\Services\SchedulerHeartbeat;
use App\Services\SystemNotificationService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * #635: a fresh install takes a backup on a schedule and applies retention
 * without any setup; a failed run tells the administrators; Site Health
 * warns when backups stop.
 */
class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    private string $stateFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateFile = storage_path('framework/testing/backup-schedule-'.uniqid().'.json');
        config(['backup.schedule.state_file' => $this->stateFile]);
        Carbon::setTestNow('2026-11-18 10:00:00'); // a Wednesday
    }

    protected function tearDown(): void
    {
        @unlink($this->stateFile);
        Carbon::setTestNow();
        unset($_SERVER['INSTALLED']);

        parent::tearDown();
    }

    public function test_a_fresh_install_backs_up_the_database_daily_for_fourteen_days(): void
    {
        $schedule = app(BackupSchedule::class);

        $this->assertTrue($schedule->enabled());
        $this->assertSame('daily', $schedule->frequency());
        $this->assertSame(['database'], $schedule->targets());
        $this->assertSame(14, $schedule->retentionDays());
        $this->assertTrue($schedule->isDue());
    }

    public function test_the_settings_drive_the_schedule(): void
    {
        SiteSetting::setValue('backup.default_retention_days', '30');
        SiteSetting::setValue(BackupSchedule::SETTING_FREQUENCY, 'weekly');
        SiteSetting::setValue(BackupSchedule::SETTING_TARGETS, json_encode(['database', 'media', 'gone']));
        $schedule = app(BackupSchedule::class);

        $this->assertSame(30, $schedule->retentionDays());
        $this->assertSame(['database', 'media'], $schedule->targets());
        $this->assertFalse($schedule->isDue(Carbon::parse('2026-11-18'))); // Wednesday
        $this->assertTrue($schedule->isDue(Carbon::parse('2026-11-22'))); // Sunday

        SiteSetting::setValue(BackupSchedule::SETTING_ENABLED, '0');
        $this->assertFalse($schedule->isDue(Carbon::parse('2026-11-22')));
    }

    public function test_the_scheduler_runs_cleanup_then_the_backup_once_installed(): void
    {
        $create = $this->scheduledEvent('dls:backup:create --scheduled');
        $cleanup = $this->scheduledEvent('dls:backup:cleanup');

        $this->assertSame('30 2 * * *', $create->expression);
        $this->assertSame('15 2 * * *', $cleanup->expression);

        $this->assertFalse($create->filtersPass($this->app), 'must not run before installation');
        $this->assertFalse($cleanup->filtersPass($this->app));

        $_SERVER['INSTALLED'] = 'true';
        $this->assertTrue($create->filtersPass($this->app));
        $this->assertTrue($cleanup->filtersPass($this->app));

        SiteSetting::setValue(BackupSchedule::SETTING_ENABLED, '0');
        $this->assertFalse($create->filtersPass($this->app));
        $this->assertTrue($cleanup->filtersPass($this->app), 'retention still applies with the schedule off');
    }

    public function test_the_scheduled_command_uses_the_schedule_and_marks_the_record(): void
    {
        $this->fakeBackups(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->once()
            ->with(['database'], ['retention_days' => 14, 'trigger' => 'scheduled'])
            ->andReturn(BackupResultDTO::success(1, '/tmp/b.zip', 10, 0.1, ['database'], ['hash' => 'h'])));

        $this->artisan('dls:backup:create --scheduled')->assertExitCode(0);
    }

    public function test_a_real_scheduled_record_carries_the_trigger_and_retention(): void
    {
        $this->artisan('dls:backup:create', ['--scheduled' => true])->assertExitCode(0);

        $record = BackupRecord::query()->latest('id')->firstOrFail();
        $this->assertSame('scheduled', $record->metadata['trigger'] ?? null);
        $this->assertSame(['database'], $record->targets);
        $this->assertTrue($record->retention_until->equalTo(now()->addDays(14)));

        @unlink($record->file_path);
    }

    public function test_a_failure_is_reported_once_and_cleared_by_the_next_success(): void
    {
        $notifications = Mockery::mock(SystemNotificationService::class);
        $notifications->shouldReceive('sendAdminNotification')->once()
            ->with(__('admin/settings/systems/backup/settings.schedule.failed_title'), Mockery::on(
                fn (string $message) => str_contains($message, 'disk full')
            ))
            ->andReturn(true);
        $this->app->instance(SystemNotificationService::class, $notifications);

        $results = [
            BackupResultDTO::failure('disk full'),
            BackupResultDTO::failure('disk full'),
            BackupResultDTO::success(1, '/tmp/b.zip', 10, 0.1, ['database']),
        ];
        $this->fakeBackups(fn (MockInterface $m) => $m->shouldReceive('backup')->andReturn(...$results));
        $monitor = app(BackupScheduleMonitor::class);

        $this->artisan('dls:backup:create --scheduled')->assertExitCode(1);
        $this->artisan('dls:backup:create --scheduled')->assertExitCode(1);
        $this->assertSame(2, $monitor->failure()['count'] ?? null);
        $this->assertSame('disk full', $monitor->failure()['error'] ?? null);

        $this->artisan('dls:backup:create --scheduled')->assertExitCode(0);
        $this->assertNull($monitor->failure());
    }

    public function test_an_exception_in_a_scheduled_run_is_recorded_as_a_failure(): void
    {
        $this->fakeBackups(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->andThrow(new \RuntimeException('cannot write')));

        $this->artisan('dls:backup:create --scheduled')->assertExitCode(1);

        $this->assertSame('cannot write', app(BackupScheduleMonitor::class)->failure()['error'] ?? null);
    }

    public function test_a_manual_run_does_not_touch_the_alert(): void
    {
        $this->fakeBackups(fn (MockInterface $m) => $m->shouldReceive('backup')
            ->andReturn(BackupResultDTO::failure('disk full')));

        $this->artisan('dls:backup:create --targets=database')->assertExitCode(1);

        $this->assertNull(app(BackupScheduleMonitor::class)->failure());
    }

    public function test_site_health_reports_each_state(): void
    {
        app(SchedulerHeartbeat::class)->touch();

        $this->assertSame('recommendation', $this->healthItem()['status'], 'no backup yet');

        $this->backupAt(now()->subHours(30));
        $this->assertSame('ok', $this->healthItem()['status']);

        Carbon::setTestNow(now()->addHours(20)); // 50 hours since the backup
        app(SchedulerHeartbeat::class)->touch();
        $item = $this->healthItem();
        $this->assertSame('warning', $item['status']);
        $this->assertStringContainsString('stopped', $item['description']);

        SiteSetting::setValue(BackupSchedule::SETTING_FREQUENCY, 'weekly');
        $this->assertSame('ok', $this->healthItem()['status'], 'a weekly schedule allows eight days');

        SiteSetting::setValue(BackupSchedule::SETTING_ENABLED, '0');
        $this->assertSame('recommendation', $this->healthItem()['status']);
    }

    public function test_site_health_warns_on_a_failed_run_and_a_stopped_scheduler(): void
    {
        app(SchedulerHeartbeat::class)->touch();
        $this->backupAt(now()->subHours(2));
        app(BackupScheduleMonitor::class)->recordScheduledRun(BackupResultDTO::failure('disk full'));

        $item = $this->healthItem();
        $this->assertSame('warning', $item['status']);
        $this->assertStringContainsString('disk full', $item['description']);

        app(BackupScheduleMonitor::class)->recordScheduledRun(BackupResultDTO::success(1, '/tmp/b.zip', 1, 0.1, ['database']));
        Cache::forget(SchedulerHeartbeat::CACHE_KEY);
        BackupRecord::query()->delete();

        $item = $this->healthItem();
        $this->assertSame('warning', $item['status'], 'no backup and no scheduler to take one');
        $this->assertStringContainsString(__('admin/dashboard.backup_scheduler_stopped'), $item['description']);
    }

    private function scheduledEvent(string $command): Event
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $e) => str_ends_with((string) $e->command, $command));
        $this->assertNotNull($event, "{$command} is not scheduled");

        return $event;
    }

    private function fakeBackups(callable $expectations): void
    {
        $mock = Mockery::mock(BackupServiceInterface::class);
        $mock->shouldReceive('getAvailableTargets')->andReturn(['database', 'media']);
        $mock->shouldReceive('getDefaultTargets')->andReturn(['database', 'media']);
        $expectations($mock);
        $this->app->instance(BackupServiceInterface::class, $mock);
    }

    private function backupAt(Carbon $at): void
    {
        $record = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => 'database',
            'targets' => ['database'],
            'file_path' => '/tmp/none.zip',
            'file_name' => 'none.zip',
            'file_size' => 1,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);
        $record->forceFill(['created_at' => $at])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function healthItem(): array
    {
        $items = collect(DashboardPresenter::siteHealth(new \App\Models\Member()));

        return $items->firstWhere('key', 'backup');
    }
}
