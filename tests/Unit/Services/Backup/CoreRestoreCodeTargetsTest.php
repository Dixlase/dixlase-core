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

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\RestoreResultDTO;
use App\Models\BackupRecord;
use App\Services\Backup\CoreRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * A restore that replaces code runs detached under maintenance mode
 * (security review X5).
 *
 * Regression: restoring core source, plugins or themes from the admin
 * screen cleared those trees and rewrote them inside the web request, with
 * no maintenance mode, and plugins / themes came back without an autoload
 * re-sync.
 */
class CoreRestoreCodeTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_targets_are_detected(): void
    {
        $service = app(CoreRestoreService::class);

        foreach ([
            BackupServiceInterface::TARGET_CORE_SOURCE,
            BackupServiceInterface::TARGET_PLUGINS_ALL,
            BackupServiceInterface::TARGET_THEMES_ALL,
        ] as $target) {
            $backup = new BackupRecord(['targets' => [BackupServiceInterface::TARGET_DATABASE, $target]]);
            $this->assertTrue($service->restoresCode($backup), "{$target} replaces code.");
        }

        $dataOnly = new BackupRecord(['targets' => [
            BackupServiceInterface::TARGET_DATABASE,
            BackupServiceInterface::TARGET_MEDIA,
            BackupServiceInterface::TARGET_PRIVATE,
        ]]);
        $this->assertFalse($service->restoresCode($dataOnly));
    }

    public function test_a_subset_without_code_is_not_a_code_restore(): void
    {
        $backup = new BackupRecord(['targets' => [
            BackupServiceInterface::TARGET_DATABASE,
            BackupServiceInterface::TARGET_PLUGINS_ALL,
        ]]);

        $this->assertFalse(app(CoreRestoreService::class)->restoresCode($backup, [BackupServiceInterface::TARGET_DATABASE]));
        $this->assertTrue(app(CoreRestoreService::class)->restoresCode($backup, [BackupServiceInterface::TARGET_PLUGINS_ALL]));
    }

    public function test_the_command_takes_the_site_down_for_a_code_restore(): void
    {
        $backup = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_DATABASE,
            'targets' => [BackupServiceInterface::TARGET_PLUGINS_ALL],
            'file_path' => '/nonexistent/backup.zip',
            'file_name' => 'backup.zip',
            'file_size' => 100,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);

        $service = Mockery::mock(CoreRestoreService::class)->makePartial();
        $service->shouldReceive('crossesDependencyBoundary')->andReturn(false);
        $service->shouldReceive('restore')->once()->andReturn(RestoreResultDTO::failure('stop here'));
        $this->app->instance(CoreRestoreService::class, $service);

        Artisan::shouldReceive('call')->with('down', Mockery::any())->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('up')->once()->andReturn(0);

        $command = $this->app->make(\App\Console\Commands\Backup\BackupRestoreCommand::class);
        $command->setLaravel($this->app);
        $tester = new \Symfony\Component\Console\Tester\CommandTester($command);
        $tester->execute(['id' => (string) $backup->id, '--force' => true]);
    }

    public function test_the_admin_controller_routes_code_restores_to_the_detached_command(): void
    {
        $source = File::get(app_path('Http/Controllers/Admin/Settings/Systems/AdminSystemBackupController.php'));

        $this->assertStringContainsString('$coreRestore->restoresCode($backup)', $source);
        $this->assertStringContainsString('$coreRestore->restoresCode($restore->preRestoreBackup)', $source);
        $this->assertStringContainsString("'--rollback-of='", $source);
    }
}
