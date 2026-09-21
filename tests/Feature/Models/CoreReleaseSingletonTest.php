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

namespace Tests\Feature\Models;

use App\Models\CoreRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CoreRelease::singleton() must always resolve the row with id = PRIMARY_ID.
 *
 * `id` is not mass-assignable, so the old firstOrCreate(['id' => 1])
 * silently dropped it and inserted at the next auto-increment value. On a
 * fresh database that happens to be 1; once the counter has moved (any
 * earlier insert — MySQL does not roll auto-increment back with a
 * transaction) every call created a new, unreachable row, and whatever was
 * written to "the singleton" (update failure reason, available version)
 * never showed up for readers looking up id 1. The installer seeds id 1
 * with updateOrInsert, so this only bites when the row is missing — which
 * is exactly when singleton() is supposed to repair it.
 */
class CoreReleaseSingletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_row_with_the_primary_id_even_after_the_counter_has_moved(): void
    {
        // Advance the auto-increment counter past 1, then empty the table.
        $stray = new CoreRelease();
        $stray->forceFill(['id' => 7])->save();
        $stray->delete();
        $this->assertSame(0, CoreRelease::query()->count());

        $row = CoreRelease::singleton();

        $this->assertSame(CoreRelease::PRIMARY_ID, (int) $row->getKey());
        $this->assertSame(1, CoreRelease::query()->count());
        $this->assertNotNull(CoreRelease::query()->find(CoreRelease::PRIMARY_ID));
    }

    public function test_repeated_calls_return_the_same_row_and_never_add_rows(): void
    {
        $stray = new CoreRelease();
        $stray->forceFill(['id' => 7])->save();
        $stray->delete();

        CoreRelease::singleton();
        CoreRelease::singleton();
        $third = CoreRelease::singleton();

        $this->assertSame(CoreRelease::PRIMARY_ID, (int) $third->getKey());
        $this->assertSame(1, CoreRelease::query()->count(), 'singleton() must not create a row per call');
    }

    public function test_writes_through_the_singleton_are_visible_at_the_primary_id(): void
    {
        $stray = new CoreRelease();
        $stray->forceFill(['id' => 7])->save();
        $stray->delete();

        CoreRelease::singleton()->forceFill(['update_failure_reason' => 'boom'])->save();

        $this->assertSame('boom', CoreRelease::query()->find(CoreRelease::PRIMARY_ID)?->update_failure_reason);
    }

    public function test_returns_the_existing_row_seeded_by_the_installer(): void
    {
        // The installer seeds the row with a raw updateOrInsert.
        DB::table('core_releases')->insert([
            'id' => CoreRelease::PRIMARY_ID,
            'available_version' => '9.9.9',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = CoreRelease::singleton();

        $this->assertSame(CoreRelease::PRIMARY_ID, (int) $row->getKey());
        $this->assertSame('9.9.9', $row->available_version);
        $this->assertSame(1, CoreRelease::query()->count());
    }
}
