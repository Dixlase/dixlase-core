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

namespace Tests\Unit\Console;

use App\Models\AuditLog;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\CoreVendorManager;
use App\Services\Update\SystemUpdateFlash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Two defects the production sandbox hit while testing admin-UI core
 * rollback across the Laravel 12/13 boundary:
 *
 *   A. A failed rollback recorded no outcome, so the System Updates page
 *      dropped its progress placeholder and showed nothing at all.
 *   B. A dependency rollback to a release without the prebuilt-vendor asset
 *      found out only after entering maintenance, reversing the schema and
 *      restoring the source, then aborted half-way.
 */
class CoreRollbackFailureReportingTest extends TestCase
{
    // The failed rollback writes a core_rollback_failed audit row; without a
    // reset it leaks into the shared test database and breaks row counts
    // in later tests.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemUpdateFlash::consume();
    }

    protected function tearDown(): void
    {
        SystemUpdateFlash::consume();
        Mockery::close();
        parent::tearDown();
    }

    public function test_missing_prebuilt_vendor_aborts_before_anything_changes_and_is_reported(): void
    {
        $snapshotter = Mockery::mock(CoreSourceSnapshot::class);
        $snapshotter->shouldReceive('latestSnapshotWithMetadata')->andReturn('/tmp/core-snapshot-test');
        $snapshotter->shouldReceive('readMetadata')->andReturn([
            'from' => '0.3.32',
            'to' => '0.3.35',
            'dependency_update' => true,
            'max_batch' => 5,
        ]);
        // Nothing may be captured, restored or consumed.
        $snapshotter->shouldNotReceive('capture');
        $snapshotter->shouldNotReceive('restore');
        $snapshotter->shouldNotReceive('discard');
        $this->app->instance(CoreSourceSnapshot::class, $snapshotter);

        $vendor = Mockery::mock(CoreVendorManager::class);
        $vendor->shouldReceive('prefetchVerifiedRelease')->once()->with('0.3.32')
            ->andThrow(new RuntimeException('Core v0.3.32 cannot be used to restore dependencies: its release has no prebuilt vendor/'));
        $vendor->shouldNotReceive('refetchAndSwap');
        $this->app->instance(CoreVendorManager::class, $vendor);

        $this->artisan('dls:core:rollback', ['--force' => true])->assertExitCode(1);

        $this->assertFalse($this->app->isDownForMaintenance(), 'The pre-flight must fail before maintenance mode.');

        $result = SystemUpdateFlash::consume();
        $this->assertIsArray($result, 'A failed rollback must record an outcome for the admin UI.');
        $this->assertSame('error', $result['status']);
        $this->assertSame('rollback', $result['operation']);
        $this->assertSame('0.3.32', $result['to']);
        $this->assertTrue($result['recovered'], 'Nothing changed, so the core is still where it was.');
        $this->assertStringContainsString('no prebuilt vendor/', $result['error']);

        $audit = AuditLog::where('action', AuditLog::ACTION_CORE_ROLLBACK_FAILED)->sole();
        $this->assertSame(AuditLog::OUTCOME_FAILURE, $audit->outcome);
        $this->assertSame('0.3.32', $audit->context['to']);
        $this->assertTrue($audit->context['recovered']);
    }

    public function test_vendor_check_runs_before_the_safety_snapshot_and_maintenance(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        $checkPos = strpos($source, '$vendorManager->prefetchVerifiedRelease($from)');
        $capturePos = strpos($source, '$safetySnapshot = $snapshotter->capture()');
        $downPos = strpos($source, "Artisan::call('down'");

        $this->assertNotFalse($checkPos);
        $this->assertNotFalse($capturePos);
        $this->assertNotFalse($downPos);
        $this->assertLessThan($capturePos, $checkPos, 'Check the target release for vendor/ before touching anything.');
        $this->assertLessThan($downPos, $checkPos, 'Check the target release for vendor/ before entering maintenance.');
    }
}
