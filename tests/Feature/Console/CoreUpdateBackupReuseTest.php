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

namespace Tests\Feature\Console;

use App\Services\Core\CoreUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

/**
 * dls:core:update --db-backup-id wiring.
 *
 * When the admin UI already captured a source-inclusive
 * [core_source, database] pre-update backup, it passes that record's id so
 * the updater reuses it as the DB restore point instead of taking a second,
 * DB-only snapshot (which used to surface as a confusing duplicate entry in
 * the backup list). These tests pin that the command threads the option
 * through to CoreUpdater::update(); the skip itself lives in the updater and
 * is covered by the live sandbox verification recorded in the PR.
 */
class CoreUpdateBackupReuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_backup_id_option_is_passed_through_to_the_updater(): void
    {
        $result = $this->fakeUpdateResult();
        $this->mock(CoreUpdater::class, function ($mock) use ($result) {
            // update(version, appliedById, existingDbBackupId, log, allowDowngrade)
            $mock->shouldReceive('update')
                ->once()
                ->with(Mockery::any(), Mockery::any(), 7, Mockery::any(), Mockery::any())
                ->andReturn($result);
        });

        $exit = Artisan::call('dls:core:update', [
            '--to' => '99.0.0',
            '--db-backup-id' => 7,
            '--force' => true,
            '--skip-build' => true,
        ]);

        $this->assertSame(0, $exit);
    }

    public function test_db_backup_id_defaults_to_null_for_direct_cli_runs(): void
    {
        $result = $this->fakeUpdateResult();
        $this->mock(CoreUpdater::class, function ($mock) use ($result) {
            $mock->shouldReceive('update')
                ->once()
                ->with(Mockery::any(), Mockery::any(), null, Mockery::any(), Mockery::any())
                ->andReturn($result);
        });

        $exit = Artisan::call('dls:core:update', [
            '--to' => '99.0.0',
            '--force' => true,
            '--skip-build' => true,
        ]);

        $this->assertSame(0, $exit);
    }

    /**
     * @return array{from: string, to: string, snapshot: string, history_id: int, backup_record_id: int}
     */
    private function fakeUpdateResult(): array
    {
        return [
            'from' => '0.0.0',
            'to' => '99.0.0',
            'snapshot' => '/tmp/snapshot',
            'history_id' => 1,
            'backup_record_id' => 7,
        ];
    }
}
