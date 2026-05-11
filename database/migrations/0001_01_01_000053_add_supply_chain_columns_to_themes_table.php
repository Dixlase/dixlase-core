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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
 * Forward-compatibility ALTER for environments that ran an earlier version
 * of 0001_01_01_000040_create_themes_table.php (before the supply-chain
 * columns were added inline).
 *
 * The columns are idempotently added with Schema::hasColumn guards so that:
 *   - Fresh installs that already have the columns via the create migration
 *     skip every block (this migration becomes a no-op).
 *   - Existing installs that pre-date the inline edit gain the same five
 *     supply-chain columns without a destructive migrate:fresh.
 *
 * Mirrors the column set on the plugins table; see docs/development/supply-chain.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            if (! Schema::hasColumn('themes', 'signing_key_id')) {
                $table->string('signing_key_id')->nullable()->after('update_failure_reason')->index();
            }
            if (! Schema::hasColumn('themes', 'author_id')) {
                $table->string('author_id')->nullable()->after('signing_key_id')->index();
            }
            if (! Schema::hasColumn('themes', 'authority_key_id')) {
                $table->string('authority_key_id')->nullable()->after('author_id');
            }
            if (! Schema::hasColumn('themes', 'installed_from_url')) {
                $table->string('installed_from_url')->nullable()->after('authority_key_id');
            }
            if (! Schema::hasColumn('themes', 'installation_method')) {
                $table->string('installation_method')->nullable()->after('installed_from_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            foreach ([
                'installation_method',
                'installed_from_url',
                'authority_key_id',
                'author_id',
                'signing_key_id',
            ] as $column) {
                if (Schema::hasColumn('themes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
