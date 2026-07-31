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

namespace Tests\Unit\Services\Core;

use App\Services\Core\CoreSourceSnapshot;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

/**
 * Pins the "resolvable from-version" filter that gates which snapshots
 * `dls:core:rollback` and the admin System Updates page will offer as
 * rollback points. Added in response to Round 4 Finding B in
 * scratchpad/core-team-tasks-dryrun8-ui-round.md — a stale `from:0.0.0`
 * snapshot from a pre-baseline update survived the last consume and
 * became the offered rollback point, which then triggered Finding C
 * (destructive fail when running-code lacks the #175 guard).
 *
 * The filter (in {@see CoreSourceSnapshot::latestSnapshotWithMetadata})
 * and the sibling pruner ({@see pruneUnresolvableSnapshots}) both
 * delegate to the same public predicate {@see hasResolvableFromVersion}
 * so UI-side `buildCoreSection` can share the check without a second
 * file read. This test file pins that predicate and the filter/prune
 * behaviours that layer on top of it.
 */
class CoreSnapshotResolvableFromTest extends TestCase
{
    private CoreSourceSnapshot $snapshotter;

    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshotter = app(CoreSourceSnapshot::class);

        // snapshotRootPath() is not injectable — it always points at
        // storage_path('app/private/core-update/snapshots'). In tests
        // that resolves to the test app's storage, so we clear it to
        // isolate this test file from any leftover snapshots.
        $reflection = new ReflectionClass($this->snapshotter);
        $method = $reflection->getMethod('snapshotRootPath');
        $this->rootPath = (string) $method->invoke($this->snapshotter);
        $this->wipeRoot();
        File::ensureDirectoryExists($this->rootPath);
    }

    protected function tearDown(): void
    {
        $this->wipeRoot();
        parent::tearDown();
    }

    // ---------- hasResolvableFromVersion (pure predicate) ----------

    public function test_predicate_true_for_a_real_version(): void
    {
        $this->assertTrue(CoreSourceSnapshot::hasResolvableFromVersion(['from' => '0.3.2-dryrun-7']));
        $this->assertTrue(CoreSourceSnapshot::hasResolvableFromVersion(['from' => '1.0.0']));
    }

    public function test_predicate_false_for_null_meta(): void
    {
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(null));
    }

    public function test_predicate_false_for_missing_from_key(): void
    {
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(['to' => '1.0.0']));
    }

    public function test_predicate_false_for_empty_from(): void
    {
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(['from' => '']));
    }

    public function test_predicate_false_for_0_0_0_placeholder(): void
    {
        // The one that actually bit dryrun-6 through dryrun-8: pre-baseline
        // installs left this placeholder in meta when the ledger was empty.
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(['from' => '0.0.0']));
    }

    public function test_predicate_false_for_non_string_from(): void
    {
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(['from' => null]));
        $this->assertFalse(CoreSourceSnapshot::hasResolvableFromVersion(['from' => 100]));
    }

    // ---------- latestSnapshotWithMetadata (filter) ----------

    public function test_latest_skips_snapshot_with_0_0_0_from_and_returns_older_resolvable_one(): void
    {
        // Older snapshot has a resolvable from, newer one has 0.0.0. The
        // 0.0.0 must be skipped and the resolvable one returned even
        // though it is not the newest on disk.
        $this->writeSnapshot('20260101000000_older', ['from' => '0.3.1', 'to' => '0.3.2']);
        $this->writeSnapshot('20260701000000_newer', ['from' => '0.0.0', 'to' => '0.3.3']);

        $result = $this->snapshotter->latestSnapshotWithMetadata();

        $this->assertNotNull($result);
        $this->assertStringEndsWith('20260101000000_older', $result);
    }

    public function test_latest_returns_null_when_only_unresolvable_snapshots_exist(): void
    {
        $this->writeSnapshot('20260101000000_a', ['from' => '0.0.0', 'to' => '0.3.2']);
        $this->writeSnapshot('20260701000000_b', ['from' => '', 'to' => '0.3.3']);

        $this->assertNull($this->snapshotter->latestSnapshotWithMetadata());
    }

    public function test_latest_still_skips_bare_snapshots_without_sidecar(): void
    {
        // Pre-existing behaviour must survive the refactor — a snapshot
        // dir with no sidecar is a pre-rollback safety capture and
        // cannot be a rollback point.
        $this->writeSnapshot('20260101000000_safety'); // no sidecar
        $this->writeSnapshot('20260701000000_real', ['from' => '0.3.2', 'to' => '0.3.3']);

        $result = $this->snapshotter->latestSnapshotWithMetadata();

        $this->assertNotNull($result);
        $this->assertStringEndsWith('20260701000000_real', $result);
    }

    // ---------- pruneUnresolvableSnapshots ----------

    public function test_prune_deletes_older_0_0_0_snapshots_but_keeps_the_newest_regardless(): void
    {
        // Newest is resolvable, older 0.0.0 must be deleted.
        $this->writeSnapshot('20260101000000_old_bad', ['from' => '0.0.0', 'to' => '0.3.2']);
        $this->writeSnapshot('20260601000000_mid_good', ['from' => '0.3.1', 'to' => '0.3.2']);
        $this->writeSnapshot('20260701000000_new_good', ['from' => '0.3.2', 'to' => '0.3.3']);

        $this->snapshotter->pruneUnresolvableSnapshots();

        $this->assertDirectoryDoesNotExist($this->rootPath.'/20260101000000_old_bad');
        $this->assertDirectoryExists($this->rootPath.'/20260601000000_mid_good');
        $this->assertDirectoryExists($this->rootPath.'/20260701000000_new_good');
    }

    public function test_prune_preserves_the_sole_snapshot_even_when_unresolvable(): void
    {
        // Fresh install with one unresolvable snapshot: the operator's
        // only record of the update they just applied. Deleting it
        // silently would remove that record with no warning; leave it.
        $this->writeSnapshot('20260101000000_only', ['from' => '0.0.0', 'to' => '0.3.3']);

        $this->snapshotter->pruneUnresolvableSnapshots();

        $this->assertDirectoryExists($this->rootPath.'/20260101000000_only');
    }

    public function test_prune_leaves_older_bare_safety_snapshots_alone(): void
    {
        // Bare (no-sidecar) older snapshots are already invisible to
        // latestSnapshotWithMetadata, so they are not a Finding-B risk.
        // The count-based prune handles their retention.
        $this->writeSnapshot('20260101000000_old_bare');   // no sidecar
        $this->writeSnapshot('20260701000000_new_good', ['from' => '0.3.2', 'to' => '0.3.3']);

        $this->snapshotter->pruneUnresolvableSnapshots();

        $this->assertDirectoryExists($this->rootPath.'/20260101000000_old_bare');
        $this->assertDirectoryExists($this->rootPath.'/20260701000000_new_good');
    }

    // ---------- helpers ----------

    /**
     * Create a snapshot directory (empty) with an optional metadata
     * sidecar. `latestSnapshotWithMetadata` / `pruneUnresolvableSnapshots`
     * only inspect the sidecar's presence and JSON contents, not the
     * dir's contents, so this is enough to exercise the code paths.
     *
     * @param  array<string,mixed>|null  $meta  null for a bare (safety) snapshot
     */
    private function writeSnapshot(string $dirName, ?array $meta = null): void
    {
        $dir = $this->rootPath.'/'.$dirName;
        File::ensureDirectoryExists($dir);
        if ($meta !== null) {
            File::put($dir.'.meta.json', (string) json_encode($meta));
        }
    }

    private function wipeRoot(): void
    {
        if (! isset($this->rootPath) || ! is_dir($this->rootPath)) {
            return;
        }
        // Delete both the snapshot dirs and the sidecars next to them.
        foreach (glob($this->rootPath.'/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                File::deleteDirectory($entry);
            } else {
                @unlink($entry);
            }
        }
        @rmdir($this->rootPath);
    }
}
