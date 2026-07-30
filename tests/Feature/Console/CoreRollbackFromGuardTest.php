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

namespace Tests\Feature\Console;

use App\Models\CoreVersionHistory;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\CoreVendorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Pins the Finding B guard added to `dls:core:rollback`: when the
 * rollback snapshot's metadata records `from: 0.0.0` (the pre-Finding-D
 * install path used to leave that placeholder in), the command must
 * refuse BEFORE touching anything — no `vendor/` re-fetch attempted,
 * no history row written, no maintenance mode entered. The sandbox
 * verification in issue #171 hit this: the rollback recovered
 * gracefully via its own catch block, but the mid-rollback state was
 * scary and unnecessary if we can just refuse up-front.
 *
 * We mock `CoreSourceSnapshot` so tests do not need a real snapshot on
 * disk — the command only asks it for the latest path and metadata,
 * both of which we stub.
 */
class CoreRollbackFromGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Stage a fake "latest snapshot with metadata" so the command
     * proceeds past its own presence checks. Returns the stubbed
     * snapshot path used in metadata assertions.
     *
     * @param  array<string, mixed>  $meta
     */
    private function stubLatestSnapshot(array $meta): string
    {
        $path = '/tmp/fake-snapshot-'.uniqid();

        $this->mock(CoreSourceSnapshot::class, function ($mock) use ($path, $meta) {
            $mock->shouldReceive('latestSnapshotWithMetadata')->andReturn($path);
            $mock->shouldReceive('readMetadata')->andReturn($meta);
            // Explicitly refuse any subsequent method call that would
            // mutate state — if the command proceeds past the guard we
            // want the test to fail with a clear expectation error, not
            // a runtime NPE from Mockery returning null on `restore()`.
            $mock->shouldNotReceive('capture');
            $mock->shouldNotReceive('restore');
            $mock->shouldNotReceive('discard');
            $mock->shouldNotReceive('writeMetadata');
        });

        // Belt-and-braces: also assert the vendor manager is never touched.
        // Rollback with dependencyUpdate=true would call refetchAndSwap;
        // the guard must fire before we reach that branch.
        $this->mock(CoreVendorManager::class, function ($mock) {
            $mock->shouldNotReceive('refetchAndSwap');
            $mock->shouldNotReceive('swap');
            $mock->shouldNotReceive('restorePrevious');
        });

        return $path;
    }

    public function test_rollback_refuses_when_from_is_the_0_0_0_placeholder(): void
    {
        $this->stubLatestSnapshot([
            'from' => '0.0.0',
            'to' => '0.3.3-dryrun-8',
            'dependency_update' => true,
            'backup_record_id' => null,
        ]);

        Artisan::call('dls:core:rollback', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Rollback target v0.0.0 is not a resolvable release tag.', $output);
        $this->assertStringContainsString('dls:core:reconcile --confirm', $output);
        $this->assertStringNotContainsString('Capturing pre-rollback safety snapshot', $output, 'The guard must fire before the safety snapshot capture — no mid-rollback state should be entered.');

        // No history row was written by the aborted rollback.
        $this->assertSame(0, CoreVersionHistory::where('installation_method', 'rollback')->count());
    }

    public function test_rollback_refuses_the_0_0_0_placeholder_even_when_dependency_update_is_false(): void
    {
        // The guard should fire regardless of dependency_update. Even
        // without a vendor re-fetch, the aborted rollback would write
        // `new_version='0.0.0'` into the ledger, poisoning
        // currentVersion() forever after.
        $this->stubLatestSnapshot([
            'from' => '0.0.0',
            'to' => '0.3.3-dryrun-8',
            'dependency_update' => false,
            'backup_record_id' => null,
        ]);

        Artisan::call('dls:core:rollback', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Rollback target v0.0.0 is not a resolvable release tag.', $output);
        $this->assertSame(0, CoreVersionHistory::where('installation_method', 'rollback')->count());
    }

    public function test_rollback_empty_from_message_still_fires_ahead_of_the_new_guard(): void
    {
        // The existing empty-from check must keep its distinct error
        // message so an unrelated regression in that path is still
        // visible. Pinning both messages here so a future refactor
        // that collapses them into one loses the specificity check.
        $this->stubLatestSnapshot([
            'from' => '',
            'to' => '0.3.3-dryrun-8',
            'dependency_update' => true,
            'backup_record_id' => null,
        ]);

        Artisan::call('dls:core:rollback', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Rollback metadata is missing the target version.', $output);
        $this->assertStringNotContainsString('0.0.0', $output, 'The empty-from case must not be conflated with the 0.0.0 case; each has its own remedial hint.');
    }
}
