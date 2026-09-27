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

namespace Tests\Feature\Services\Core;

use App\Models\FileIntegrityAudit;
use App\Models\Member;
use App\Services\Core\CoreIntegrityBaselineRefresher;
use App\Services\FileIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * The core integrity baseline follows successful updates and rollbacks —
 * but only when the tree matched it before the operation started.
 *
 * Regression: core_hashes.json was written at install and never again, so
 * the first legitimate update (0.3.56 → 0.3.57) turned the daily scan from
 * "ok, 1949 files" into "warning, 6 changed, 12 added". Regenerating it
 * unconditionally would bless tampering instead, hence the precondition.
 *
 * FileIntegrityService is mocked: the real one hashes the working tree and
 * writes storage/app/dixlase/security/core_hashes.json.
 */
class CoreIntegrityBaselineRefresherTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = ['meta' => ['app_version' => '0.3.58'], 'files' => ['app/A.php' => 'aaa']];

    private const NEW = ['meta' => ['app_version' => '0.3.59'], 'files' => ['app/A.php' => 'bbb', 'app/B.php' => 'ccc']];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_the_tree_matches_when_its_hashes_equal_the_baseline(): void
    {
        $this->assertTrue($this->refresher(self::OLD, self::OLD)->treeMatchesBaseline());
        $this->assertFalse($this->refresher(self::OLD, self::NEW)->treeMatchesBaseline());
        $this->assertNull($this->refresher(null, self::NEW)->treeMatchesBaseline());
    }

    public function test_a_successful_update_writes_a_new_baseline_and_records_it(): void
    {
        $integrity = $this->integrity(self::OLD, self::NEW);
        $integrity->shouldReceive('saveBaselineArray')->once()->with(self::NEW)->andReturn(true);
        $lines = [];
        $member = Member::factory()->create();

        (new CoreIntegrityBaselineRefresher($integrity))->refreshAfter(true, $member->id, function (string $l) use (&$lines) {
            $lines[] = $l;
        });

        $audit = FileIntegrityAudit::sole();
        $this->assertSame(FileIntegrityAudit::TRIGGER_UPDATE, $audit->trigger);
        $this->assertSame(FileIntegrityAudit::INITIATED_BY_USER, $audit->initiated_by_type);
        $this->assertSame($member->id, (int) $audit->initiated_by_id);
        $this->assertSame('0.3.59', $audit->baseline_version);
        $this->assertSame(2, (int) $audit->total_files_scanned);
        $this->assertStringContainsString('Integrity baseline regenerated (2 files)', implode("\n", $lines));
    }

    public function test_a_tree_that_was_already_modified_keeps_its_baseline(): void
    {
        $integrity = Mockery::mock(FileIntegrityService::class);
        $integrity->shouldNotReceive('saveBaselineArray');
        $lines = [];

        (new CoreIntegrityBaselineRefresher($integrity))->refreshAfter(false, null, function (string $l) use (&$lines) {
            $lines[] = $l;
        });

        $this->assertSame(0, FileIntegrityAudit::count());
        $this->assertStringContainsString('baseline was left unchanged', implode("\n", $lines));
    }

    public function test_update_and_rollback_refresh_the_baseline_after_success(): void
    {
        $updater = (string) file_get_contents(app_path('Services/Core/CoreUpdater.php'));
        $rollback = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        foreach (['CoreUpdater' => $updater, 'CoreRollback' => $rollback] as $name => $source) {
            $before = strpos($source, '$integrityMatchedBefore = $this->integrityMatchesBaseline(');
            $capture = strpos($source, '->capture()');
            $refresh = strpos($source, 'refreshAfter(');

            $this->assertNotFalse($before, "{$name} must check the baseline before it changes anything.");
            $this->assertLessThan($capture, $before, "{$name}: the check must come before the snapshot.");
            $this->assertNotFalse($refresh, "{$name} must refresh the baseline after success.");
        }
    }

    private function refresher(?array $baseline, array $current): CoreIntegrityBaselineRefresher
    {
        return new CoreIntegrityBaselineRefresher($this->integrity($baseline, $current));
    }

    private function integrity(?array $baseline, array $current): FileIntegrityService
    {
        $integrity = Mockery::mock(FileIntegrityService::class);
        $integrity->shouldReceive('loadBaselineArray')->andReturn($baseline);
        $integrity->shouldReceive('generateCoreBaseline')->andReturn($current);

        return $integrity;
    }
}
