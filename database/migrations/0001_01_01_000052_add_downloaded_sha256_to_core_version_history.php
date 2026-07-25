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
 * Foundation for future update-integrity verification.
 *
 * Records the SHA-256 of the release ZIP that a core update actually
 * downloaded, so a later release that adds ZIP signature verification
 * can retro-audit past updates against the authority's published
 * hash / signature. Populated by CoreUpdater::update() during download;
 * kept null for pre-foundation history rows (backwards compatible).
 *
 * NOTE (beta migration workflow):
 *   This is an intermediate ADD migration for production sites (brand,
 *   keys, docs) that are already running the old schema. Once those
 *   sites have applied it, this file will be merged into
 *   `0001_01_01_000010_create_core_version_history_table.php` (the
 *   initial-release migration) and this file will be deleted, per the
 *   Beta migration policy in shared-rules "Migration Editing Policy".
 *   Fresh installs will then pick up the column via the merged CREATE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_version_history', function (Blueprint $table) {
            // Fixed-length hex string; SHA-256 always renders as 64
            // lowercase hex chars, so char(64) beats string() on space
            // and lets an index be tighter if we ever want one.
            $table->char('downloaded_sha256', 64)->nullable()->after('installed_from_url');
        });
    }

    public function down(): void
    {
        Schema::table('core_version_history', function (Blueprint $table) {
            $table->dropColumn('downloaded_sha256');
        });
    }
};
