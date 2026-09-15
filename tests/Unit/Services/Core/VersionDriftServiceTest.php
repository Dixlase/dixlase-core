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

use App\Services\Core\VersionDriftService;
use Tests\TestCase;

/**
 * Pins VersionDriftService's kind classification. The service is pure
 * computation — the injection points that read on-disk / ledger are
 * mockable via the two override parameters, so no DB, no filesystem.
 * These cases exhaustively cover the classification matrix so a
 * regression that mis-classifies drift as "same" cannot slip through
 * (and thereby fail to render the warning banner that is the whole
 * point of Finding #5).
 */
class VersionDriftServiceTest extends TestCase
{
    public function test_reports_same_when_both_sides_equal(): void
    {
        $result = (new VersionDriftService())->classify(onDisk: '0.3.1', ledger: '0.3.1');

        $this->assertTrue($result['known']);
        $this->assertFalse($result['drifted']);
        $this->assertSame('same', $result['kind']);
    }

    public function test_reports_ahead_when_on_disk_is_newer_than_ledger(): void
    {
        // The observed 2026-07-27 sandbox bug shape: checkout advanced
        // via git (on-disk newer), ledger still remembers old version.
        $result = (new VersionDriftService())->classify(onDisk: '0.3.1', ledger: '0.2.4');

        $this->assertTrue($result['known']);
        $this->assertTrue($result['drifted']);
        $this->assertSame('ahead', $result['kind']);
        $this->assertSame('0.3.1', $result['on_disk']);
        $this->assertSame('0.2.4', $result['ledger']);
    }

    public function test_reports_behind_when_ledger_is_newer_than_on_disk(): void
    {
        // Reverse-drift case (rare; a rollback that did not clean up,
        // or a hand-edited VERSION file). Reconcile refuses this
        // without --force.
        $result = (new VersionDriftService())->classify(onDisk: '0.2.4', ledger: '0.3.1');

        $this->assertTrue($result['known']);
        $this->assertTrue($result['drifted']);
        $this->assertSame('behind', $result['kind']);
    }

    public function test_reports_unknown_when_on_disk_is_null(): void
    {
        // Very early install: no VERSION file at the repo root yet.
        // Report known=false so the caller does not raise a warning
        // the operator cannot act on.
        $result = (new VersionDriftService())->classify(null, '0.3.1');

        $this->assertFalse($result['known']);
        $this->assertFalse($result['drifted']);
        $this->assertSame('unknown', $result['kind']);
        $this->assertNull($result['on_disk']);
        $this->assertSame('0.3.1', $result['ledger']);
    }

    public function test_reports_unknown_when_ledger_is_null(): void
    {
        // Brand-new install: no history rows yet, currentVersion() returns null.
        $result = (new VersionDriftService())->classify(onDisk: '0.3.1', ledger: null);

        $this->assertFalse($result['known']);
        $this->assertFalse($result['drifted']);
        $this->assertSame('unknown', $result['kind']);
    }

    public function test_reports_unknown_when_both_are_null(): void
    {
        $result = (new VersionDriftService())->classify(null, null);

        $this->assertFalse($result['known']);
        $this->assertFalse($result['drifted']);
        $this->assertSame('unknown', $result['kind']);
    }

    public function test_semver_prerelease_suffixes_participate_in_comparison(): void
    {
        // version_compare treats -dryrun as pre-release (older than the
        // bare version). Pinning this because if someone regresses the
        // service to compare strings lexically, dryrun tags would flip.
        $result = (new VersionDriftService())->classify(onDisk: '0.3.1-dryrun-6', ledger: '0.3.1');

        $this->assertTrue($result['drifted']);
        // On-disk `0.3.1-dryrun-6` is OLDER than ledger `0.3.1` per semver.
        $this->assertSame('behind', $result['kind']);
    }

    public function test_two_argument_calls_report_no_manifest(): void
    {
        // Existing callers pass only VERSION and the ledger; the manifest
        // keys must default to "nothing known", never to a false alarm.
        $result = (new VersionDriftService())->classify(onDisk: '0.3.27', ledger: '0.3.27');

        $this->assertNull($result['manifest']);
        $this->assertFalse($result['manifest_drifted']);
    }

    public function test_flags_manifest_that_disagrees_with_version_file(): void
    {
        // Round 2 Finding #1 shape: the update wrote VERSION=0.3.27 and the
        // ledger agrees, but dixlase.json was left at 0.3.26.
        $result = (new VersionDriftService())->classify(onDisk: '0.3.27', ledger: '0.3.27', manifest: '0.3.26');

        $this->assertTrue($result['manifest_drifted']);
        $this->assertSame('0.3.26', $result['manifest']);
        // Ledger drift is a separate fault with a separate fix (reconcile);
        // a manifest mismatch must not masquerade as one.
        $this->assertFalse($result['drifted']);
        $this->assertSame('same', $result['kind']);
    }

    public function test_matching_manifest_is_not_flagged(): void
    {
        $result = (new VersionDriftService())->classify(onDisk: '0.3.27', ledger: '0.3.26', manifest: '0.3.27');

        $this->assertFalse($result['manifest_drifted']);
        $this->assertTrue($result['drifted']);
    }

    public function test_manifest_is_not_flagged_when_version_file_is_unknown(): void
    {
        $result = (new VersionDriftService())->classify(onDisk: null, ledger: '0.3.27', manifest: '0.3.26');

        $this->assertFalse($result['manifest_drifted']);
        $this->assertSame('0.3.26', $result['manifest']);
    }

    public function test_reads_manifest_version_from_disk(): void
    {
        // Throwaway directory under storage/framework/testing — never the
        // repository's real dixlase.json.
        $dir = storage_path('framework/testing/version-drift-'.uniqid());
        mkdir($dir, 0777, true);

        try {
            $this->assertNull(VersionDriftService::readManifestVersionFromDisk($dir), 'missing file');

            file_put_contents($dir.'/dixlase.json', '{"name": "dixlase", "version": " 0.3.27 "}');
            $this->assertSame('0.3.27', VersionDriftService::readManifestVersionFromDisk($dir));

            file_put_contents($dir.'/dixlase.json', '{not json');
            $this->assertNull(VersionDriftService::readManifestVersionFromDisk($dir), 'invalid JSON');

            file_put_contents($dir.'/dixlase.json', '{"version": ""}');
            $this->assertNull(VersionDriftService::readManifestVersionFromDisk($dir), 'empty version');
        } finally {
            @unlink($dir.'/dixlase.json');
            @rmdir($dir);
        }
    }
}
