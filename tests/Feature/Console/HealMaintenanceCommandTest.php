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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\CoreRelease;
use App\Services\Core\CoreMaintenanceGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * End-to-end: a real `artisan down` (the same sentinel public/index.php
 * checks), an owner record whose process is reported dead, and the
 * scheduled command lifting the window. Only the owner file location and
 * the pid probe are substituted; `up` is the real one.
 *
 * tearDown lifts maintenance if a failing test leaves it on, otherwise
 * every later test in the run would see 503s (same guard as
 * PreventRequestsDuringMaintenanceTest).
 */
class HealMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/heal-maintenance-'.uniqid());
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        if (file_exists(CoreMaintenanceGuard::defaultSentinelPath())) {
            Artisan::call('up');
        }
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_lifts_maintenance_left_by_a_dead_core_update_and_records_the_failure(): void
    {
        $guard = $this->bindGuard(pidAlive: false);
        $secret = $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE, '9.9.9');
        Artisan::call('down', ['--secret' => $secret]);
        $this->assertFileExists(CoreMaintenanceGuard::defaultSentinelPath());

        // Assert on the command's own stream: the guard calls
        // Artisan::call('up') internally, so Artisan::output() would only
        // hold that nested call's "Application is now live." line.
        $this->artisan('dls:core:heal-maintenance')
            ->expectsOutputToContain('Lifted maintenance mode')
            ->assertSuccessful();

        $this->assertFileDoesNotExist(CoreMaintenanceGuard::defaultSentinelPath(), 'maintenance sentinel removed');
        $this->assertNull($guard->readOwner(), 'owner record removed');

        // If the core_releases write failed, say why (the guard swallows it
        // on purpose so a DB hiccup can never keep the site down).
        $this->assertNull($guard->lastRecordError(), 'failure could not be recorded on core_releases');
        $state = CoreRelease::query()->find(CoreRelease::PRIMARY_ID);
        $this->assertNotNull($state, 'core_releases singleton row exists');
        $this->assertNotNull($state->update_failed_at, 'panel is told the update did not complete');
        $this->assertStringContainsString('lifted automatically', (string) $state->update_failure_reason);
    }

    public function test_leaves_maintenance_in_place_while_the_owner_is_still_running(): void
    {
        $guard = $this->bindGuard(pidAlive: true);
        $secret = $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE, '9.9.9');
        Artisan::call('down', ['--secret' => $secret]);

        $this->artisan('dls:core:heal-maintenance')
            ->expectsOutputToContain('still appears to be running')
            ->assertSuccessful();

        $this->assertFileExists(CoreMaintenanceGuard::defaultSentinelPath());
        $this->assertNotNull($guard->readOwner());
    }

    public function test_never_lifts_a_manual_artisan_down(): void
    {
        $this->bindGuard(pidAlive: false);
        Artisan::call('down');

        $this->artisan('dls:core:heal-maintenance')
            ->expectsOutputToContain('enabled manually')
            ->assertSuccessful();

        $this->assertFileExists(CoreMaintenanceGuard::defaultSentinelPath());
    }

    public function test_dry_run_reports_without_lifting(): void
    {
        $guard = $this->bindGuard(pidAlive: false);
        $secret = $guard->claim(CoreMaintenanceGuard::OPERATION_ROLLBACK, '1.0.0');
        Artisan::call('down', ['--secret' => $secret]);

        $this->artisan('dls:core:heal-maintenance', ['--dry-run' => true])
            ->expectsOutputToContain('would lift maintenance mode')
            ->assertSuccessful();

        $this->assertFileExists(CoreMaintenanceGuard::defaultSentinelPath());
        $this->assertNotNull($guard->readOwner());
    }

    private function bindGuard(?bool $pidAlive): CoreMaintenanceGuard
    {
        $guard = new CoreMaintenanceGuard(
            ownerPath: $this->dir.'/.maintenance-owner',
            pidProbe: static fn (int $pid): ?bool => $pidAlive,
        );
        $this->app->instance(CoreMaintenanceGuard::class, $guard);

        return $guard;
    }
}
