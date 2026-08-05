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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Smoke-test migration shipped ONLY in the v0.3.15-dryrun-20 pre-release.
 * Purpose: give the sandbox a real schema change to exercise the update /
 * rollback migration path end-to-end for the first time.
 *
 * The pair is:
 *   - dryrun-19: baseline, no dummy migration → new table + column absent.
 *   - dryrun-20: this file → new table + column present after `migrate`.
 *   - rollback dryrun-20 → dryrun-19: `down()` here reverses both, and the
 *     source-tree swap removes this file from disk.
 *
 * Both directions are schema-only and safe:
 *   - `core_migration_smoke` (physical name `dls_core_migration_smoke`
 *     after the DB `prefix` config in `config/database.php`) is a
 *     brand-new table (no FKs into it, no data flowing through it in
 *     production code).
 *   - `sites.migration_smoke_flag` (physical column
 *     `dls_sites.migration_smoke_flag`) is a nullable string with no default
 *     — adding it never affects existing rows, dropping it never loses
 *     data anyone put there via the shipped code (nothing writes to it).
 *
 * This migration is dryrun-only. It ships in the v0.3.15-dryrun-20 release
 * ZIP and nowhere else; the release lineage will drop this file in the
 * next dryrun tag once verification is complete. Pre-release ZIPs are not
 * distributed to production installs, so no production database ever
 * sees these objects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_migration_smoke', function (Blueprint $table): void {
            $table->id();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('sites', function (Blueprint $table): void {
            // Nullable so adding it never touches existing rows.
            $table->string('migration_smoke_flag')->nullable()->after('is_primary');
        });
    }

    public function down(): void
    {
        // Reverse the column-add first — dropping it requires the parent
        // table to exist, and reversing the table-create after gets us
        // back to the pre-up state cleanly.
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropColumn('migration_smoke_flag');
        });

        Schema::dropIfExists('core_migration_smoke');
    }
};
