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

namespace Tests\Unit\Services\Core;

use App\Services\Core\CoreMaintenanceGuard;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * CoreMaintenanceGuard decides whether a maintenance window may be
 * lifted. Everything that touches the real system is injected: the owner
 * file and the sentinel live in a throwaway dir under
 * storage/framework/testing, the lifter is a spy instead of
 * `Artisan::call('up')`, and process liveness is a closure.
 */
class CoreMaintenanceGuardTest extends TestCase
{
    private string $dir;

    private string $owner;

    private string $sentinel;

    /** @var list<int> */
    private array $lifts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/maintenance-guard-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        $this->owner = $this->dir.'/.maintenance-owner';
        $this->sentinel = $this->dir.'/maintenance.php';
        $this->lifts = [];
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_claim_writes_the_owner_record_and_returns_the_secret(): void
    {
        $guard = $this->guard();

        $secret = $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE, '1.2.3');

        $this->assertNotSame('', $secret);
        $owner = $guard->readOwner();
        $this->assertIsArray($owner);
        $this->assertSame('update', $owner['operation']);
        $this->assertSame(getmypid(), $owner['pid']);
        $this->assertSame('1.2.3', $owner['target']);
        $this->assertSame($secret, $owner['secret']);
        $this->assertNotEmpty($owner['started_at']);

        $guard->release();
        $this->assertNull($guard->readOwner());
    }

    public function test_nothing_happens_when_maintenance_is_not_active(): void
    {
        $guard = $this->guard(pidAlive: false);
        $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE);

        $this->assertNull($guard->healIfOrphaned());

        $this->assertSame([], $this->lifts, 'must not call up when the site is not down');
        $this->assertNull($guard->readOwner(), 'a leftover owner without a sentinel is tidied away');
    }

    public function test_manual_maintenance_without_an_owner_is_never_lifted(): void
    {
        touch($this->sentinel);
        $guard = $this->guard(pidAlive: false);

        $this->assertNull($guard->healIfOrphaned());

        $this->assertSame([], $this->lifts);
        $this->assertFileExists($this->sentinel);
    }

    public function test_window_owned_by_a_dead_process_is_lifted(): void
    {
        touch($this->sentinel);
        $guard = $this->guard(pidAlive: false);
        $guard->claim(CoreMaintenanceGuard::OPERATION_ROLLBACK, '1.0.0');

        $healed = $guard->healIfOrphaned();

        $this->assertIsArray($healed);
        $this->assertSame('rollback', $healed['operation']);
        $this->assertSame(getmypid(), $healed['pid']);
        $this->assertStringContainsString('no longer running', $healed['reason']);
        $this->assertCount(1, $this->lifts, 'up is called exactly once');
        $this->assertNull($guard->readOwner(), 'owner record is dropped after lifting');
    }

    public function test_window_owned_by_a_live_process_is_left_alone(): void
    {
        touch($this->sentinel);
        $guard = $this->guard(pidAlive: true);
        $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE);

        $this->assertNull($guard->healIfOrphaned());

        $this->assertSame([], $this->lifts);
        $this->assertFileExists($this->sentinel);
        $this->assertNotNull($guard->readOwner());
    }

    public function test_unknown_liveness_falls_back_to_the_stale_threshold(): void
    {
        touch($this->sentinel);
        $guard = $this->guard(pidAlive: null);

        // Fresh record: cannot tell whether the process is alive, and it
        // has not been long — leave it.
        $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE);
        $this->assertNull($guard->healIfOrphaned());
        $this->assertSame([], $this->lifts);

        // Same record, back-dated past the threshold — lift it.
        $owner = $guard->readOwner();
        $owner['started_at'] = now()->subSeconds(CoreMaintenanceGuard::STALE_SECONDS + 60)->toIso8601String();
        file_put_contents($this->owner, json_encode($owner));

        $healed = $guard->healIfOrphaned();
        $this->assertIsArray($healed);
        $this->assertStringContainsString('cannot be probed', $healed['reason']);
        $this->assertCount(1, $this->lifts);
    }

    public function test_orphan_reason_prefers_the_pid_probe_over_age(): void
    {
        $here = gethostname();

        $guard = $this->guard(pidAlive: true);
        $ancient = ['pid' => 1, 'hostname' => $here, 'started_at' => '2000-01-01T00:00:00+00:00'];

        $this->assertNull($guard->orphanReason($ancient), 'a live pid wins even when the record is ancient');

        $guard = $this->guard(pidAlive: false);
        $fresh = ['pid' => 1, 'hostname' => $here, 'started_at' => now()->toIso8601String()];

        $this->assertSame('is no longer running', $guard->orphanReason($fresh), 'a dead pid wins even when the record is fresh');
    }

    /**
     * A pid is only meaningful inside the namespace that produced it. In the
     * standard Docker layout the scheduler runs in a separate `cron`
     * container, so probing the app container's pid there answers "no such
     * process" for a perfectly healthy update. Observed on the sandbox
     * 2026-09-23/24: maintenance was lifted twice with ~31 s and ~72 s of a
     * running vendor swap still to go.
     */
    public function test_a_pid_from_another_host_is_never_treated_as_dead(): void
    {
        $guard = $this->guard(pidAlive: false);

        $freshForeign = [
            'pid' => 1,
            'hostname' => 'some-other-container',
            'started_at' => now()->toIso8601String(),
        ];

        $this->assertNull(
            $guard->orphanReason($freshForeign),
            'a dead-looking pid from another host must not end a running operation'
        );
    }

    public function test_a_foreign_owner_is_still_cleaned_up_once_it_goes_stale(): void
    {
        $guard = $this->guard(pidAlive: false);

        $staleForeign = [
            'pid' => 1,
            'hostname' => 'some-other-container',
            'started_at' => now()->subSeconds(CoreMaintenanceGuard::STALE_SECONDS + 60)->toIso8601String(),
        ];

        $reason = $guard->orphanReason($staleForeign);

        $this->assertIsString($reason, 'the age rule still applies to a foreign owner');
        $this->assertStringContainsString('another host', $reason);
        $this->assertStringContainsString('some-other-container', $reason);
    }

    public function test_an_owner_record_without_a_hostname_is_not_trusted_to_the_probe(): void
    {
        // Records written before the hostname was recorded, or by anything
        // that did not set it: we cannot tell whose namespace the pid belongs
        // to, so the safe reading is "unknown", not "dead".
        $guard = $this->guard(pidAlive: false);

        $this->assertNull($guard->orphanReason([
            'pid' => 1,
            'started_at' => now()->toIso8601String(),
        ]));
    }

    public function test_the_heal_leaves_a_running_operation_owned_by_another_container_alone(): void
    {
        touch($this->sentinel);
        $guard = $this->guard(pidAlive: false);
        $guard->claim(CoreMaintenanceGuard::OPERATION_UPDATE, '1.0.0');

        // Rewrite the record as if the app container had claimed it and this
        // process were the scheduler in the cron container.
        $owner = $guard->readOwner();
        $owner['hostname'] = 'app-container';
        file_put_contents($this->owner, json_encode($owner));

        $this->assertNull($guard->healIfOrphaned());
        $this->assertSame([], $this->lifts, 'maintenance is not lifted');
        $this->assertFileExists($this->sentinel);
        $this->assertNotNull($guard->readOwner(), 'the owner record survives');
    }

    private function guard(?bool $pidAlive = null): CoreMaintenanceGuard
    {
        return new CoreMaintenanceGuard(
            ownerPath: $this->owner,
            sentinelPath: $this->sentinel,
            lifter: function (): void {
                $this->lifts[] = time();
                @unlink($this->sentinel);
            },
            pidProbe: static fn (int $pid): ?bool => $pidAlive,
        );
    }
}
