<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder that `dls:core:update` runs after migrations, when the class is
 * present. It is deliberately NOT registered in DatabaseSeeder, so a fresh
 * install never runs it — only an update does.
 *
 * Round 2 (v0.3.27) uses it as a verification marker. `dls:core:rollback`
 * reverses migrations but never re-runs or undoes seeders, so this marker
 * survives a rollback while the sibling row written by
 * `0001_01_01_000901_add_core_update_migration_marker` disappears. That
 * contrast is what the sandbox asserts.
 *
 * Everything here must stay safe to re-run: the seeder fires on every core
 * update, so use updateOrInsert / insertOrIgnore rather than plain inserts.
 */
class UpdateSeeder extends Seeder
{
    private const MARKER_NAME = 'core_update_seeder_marker';

    public function run(): void
    {
        DB::table('global_settings')->updateOrInsert(
            ['name' => self::MARKER_NAME],
            ['value' => '0.3.27', 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
