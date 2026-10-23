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

use App\Support\Content\FileContentColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the hashes the front page needs to publish file-stored content
     * through the admin (#555). Existing rows keep NULL: the published hash
     * is computed from the columns until the first check stores it, and the
     * files are compared with the columns, not with a hash taken from the
     * files, so a file already changed outside the admin is not taken as
     * published.
     *
     * Added as a new migration because released versions have already run
     * the create migration. Idempotent.
     */
    public function up(): void
    {
        if (! Schema::hasTable('front_pages') || Schema::hasColumn('front_pages', FileContentColumns::PUBLISHED_HASH)) {
            return;
        }

        Schema::table('front_pages', function (Blueprint $table) {
            FileContentColumns::add($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('front_pages') && Schema::hasColumn('front_pages', FileContentColumns::PUBLISHED_HASH)) {
            Schema::table('front_pages', function (Blueprint $table) {
                FileContentColumns::drop($table);
            });
        }
    }
};
