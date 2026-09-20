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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Verification migration shipped in v0.3.35.
 *
 * Purpose: prove that `dls:core:update` runs migrations forward and that
 * `dls:core:rollback` reverses them, on the run that verifies a core
 * update / rollback leaves a CLI-installed plugin working (#330: the CLI
 * install now syncs the plugin autoload and links the plugin to its
 * source). `up()` writes a marker row into `global_settings`; `down()`
 * deletes it, so the row's absence after a rollback is direct evidence
 * that `migrate:rollback` ran.
 *
 * It writes a new key instead of reusing an earlier round's. The sandbox
 * baseline is a fresh install of v0.3.34, and a fresh install already runs
 * migrations 000901 through 000904, so those rows exist before the update
 * and cannot show anything. A separate key is absent on the baseline,
 * present after the update and gone after the rollback, while the earlier
 * rows stay untouched throughout.
 *
 * Read this together with `database/seeders/UpdateSeeder.php`, whose marker
 * survives the rollback. The row is inert — nothing in the shipped code reads
 * it — and `updateOrInsert` keeps both directions safe to re-run.
 */
return new class extends Migration
{
    private const MARKER_NAME = 'core_update_round5_migration_marker';

    public function up(): void
    {
        DB::table('global_settings')->updateOrInsert(
            ['name' => self::MARKER_NAME],
            ['value' => '0.3.35', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('global_settings')
            ->where('name', self::MARKER_NAME)
            ->delete();
    }
};
