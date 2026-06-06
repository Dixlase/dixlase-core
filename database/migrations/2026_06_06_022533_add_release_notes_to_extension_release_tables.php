<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `release_notes` text column to every table that already carries
 * release metadata (`core_releases`, `plugins`, `themes`). The GitHub
 * Releases API response already includes the body (Markdown) under the
 * `body` field, and GitHubSourceProvider maps it to `ReleaseInfo::changelog`
 * — but ExtensionSourceManager's check*Updates() methods currently drop
 * that field on the floor when persisting. This migration adds the
 * destination so a companion controller change can write it, and the
 * admin updates page can render it under each row's available-version
 * cell so operators can see what changes before clicking "更新".
 *
 * Column shape mirrors the existing `update_failure_reason` text column
 * on each of the three tables (nullable, no default, no length cap) —
 * release notes can easily exceed the 65k MySQL TEXT limit but ext
 * authors should be encouraged to summarise, and going to MEDIUMTEXT
 * here would be a per-DB-engine decision better deferred until we see
 * an actual length problem in the wild.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_releases', function (Blueprint $table) {
            $table->text('release_notes')->nullable()->after('release_url');
        });

        Schema::table('plugins', function (Blueprint $table) {
            $table->text('release_notes')->nullable()->after('release_url');
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->text('release_notes')->nullable()->after('release_url');
        });
    }

    public function down(): void
    {
        Schema::table('core_releases', function (Blueprint $table) {
            $table->dropColumn('release_notes');
        });

        Schema::table('plugins', function (Blueprint $table) {
            $table->dropColumn('release_notes');
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('release_notes');
        });
    }
};
