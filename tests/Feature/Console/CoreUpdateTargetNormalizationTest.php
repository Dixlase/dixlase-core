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

use App\Services\Core\CoreUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Pins the `--to` normalisation added in response to issue #171
 * Finding C: `--to=v0.3.2-dryrun-7` (v-prefixed git-tag form) must be
 * accepted and forwarded to the updater as the un-prefixed ledger
 * form (`0.3.2-dryrun-7`). Before the fix, the leading `v` slipped
 * through into `version_compare`, mis-ordered the target against the
 * DB's un-prefixed value, and produced the doubled-`v` error
 * "Already at v0.0.0 (target vv0.3.2-dryrun-7 is not newer)".
 */
class CoreUpdateTargetNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leading_v_prefix_on_target_is_stripped_before_forward_to_updater(): void
    {
        $received = null;
        $this->mock(CoreUpdater::class, function ($mock) use (&$received) {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(function ($version, $appliedById, $backupId, $log, $allowDowngrade) use (&$received) {
                    $received = $version;

                    return true;
                })
                ->andReturn([
                    'from' => '0.0.0',
                    'to' => '0.3.2-dryrun-7',
                    'snapshot' => '/tmp/snap',
                    'history_id' => 1,
                    'backup_record_id' => null,
                ]);
        });

        $exit = Artisan::call('dls:core:update', [
            '--to' => 'v0.3.2-dryrun-7',
            '--force' => true,
            '--skip-build' => true,
        ]);

        $this->assertSame(0, $exit, 'Expected the update to reach the updater instead of being refused by CLI preflight.');
        $this->assertSame(
            '0.3.2-dryrun-7',
            $received,
            'The leading `v` on --to must be trimmed before being forwarded to CoreUpdater::update() — the ledger stores versions without the prefix.'
        );
    }

    public function test_target_without_v_prefix_is_forwarded_unchanged(): void
    {
        $received = null;
        $this->mock(CoreUpdater::class, function ($mock) use (&$received) {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(function ($version) use (&$received) {
                    $received = $version;

                    return true;
                })
                ->andReturn([
                    'from' => '0.0.0',
                    'to' => '0.3.2-dryrun-7',
                    'snapshot' => '/tmp/snap',
                    'history_id' => 1,
                    'backup_record_id' => null,
                ]);
        });

        $exit = Artisan::call('dls:core:update', [
            '--to' => '0.3.2-dryrun-7',
            '--force' => true,
            '--skip-build' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame('0.3.2-dryrun-7', $received);
    }

    public function test_multiple_leading_v_characters_are_all_stripped(): void
    {
        // Belt-and-braces: `ltrim($x, 'v')` strips every leading `v`, so
        // `vv0.3.2` becomes `0.3.2`. Pin the semantics so a future
        // refactor to `Str::of()->after('v')` (single-char strip) does
        // not silently regress the edge case.
        $received = null;
        $this->mock(CoreUpdater::class, function ($mock) use (&$received) {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(function ($version) use (&$received) {
                    $received = $version;

                    return true;
                })
                ->andReturn([
                    'from' => '0.0.0',
                    'to' => '0.3.2-dryrun-7',
                    'snapshot' => '/tmp/snap',
                    'history_id' => 1,
                    'backup_record_id' => null,
                ]);
        });

        Artisan::call('dls:core:update', [
            '--to' => 'vv0.3.2-dryrun-7',
            '--force' => true,
            '--skip-build' => true,
        ]);

        $this->assertSame('0.3.2-dryrun-7', $received);
    }
}
