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
 * Reserve the storage slot for per-member profile icons (avatars).
 *
 * The avatar feature itself is not part of the initial release; this
 * migration only adds the nullable column so that live beta sites
 * (brand / demo / keys / docs) pick the schema up through the ordinary
 * `php artisan migrate` that `CoreUpdater` runs during a core update,
 * without a destructive `migrate:fresh`.
 *
 * Deliberately a plain path string rather than a foreign key to
 * `media.id`: `media` is site-scoped (it carries `site_id` and the
 * `BelongsToSite` global scope) while `members` is shared across the
 * whole network, so a member's icon must not belong to any single
 * site's media library. Avatars are stored outside the media library
 * under their own directory, and only the stored filename goes here —
 * the same convention `media.path` uses.
 *
 * Beta bookkeeping: per the "Migration Editing Policy" in CLAUDE.md the
 * lock lands at GA, so before the GA tag this column is folded into
 * `0001_01_01_000027_create_members_table.php`, this file is deleted,
 * and each live site clears the orphaned ledger row with
 * `php artisan dls:migration:resync --prune --confirm`.
 */
return new class extends Migration
{
    protected $table = 'members';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn($this->table, 'avatar_path')) {
            // Already folded into the CREATE migration (or applied by an
            // earlier run). Recording this migration without touching the
            // schema keeps the ledger consistent on every site.
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('description')
                ->comment('Stored filename of the profile icon; null falls back to the default icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn($this->table, 'avatar_path')) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
