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

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Keys the audit log hash chain (#665).
     *
     * - `audit_logs.hash_key_version`: which integrity key hashed the row.
     *   0 is a row hashed before keying (plain SHA-256), which every
     *   existing row is.
     * - `audit_log_key_epochs`: the first row each key version hashed,
     *   signed with that key. Verification refuses an unkeyed row after
     *   the first epoch, so rewriting keyed rows as unkeyed ones does not
     *   pass.
     *
     * Added as a new migration because released versions have already run
     * the audit tables' create migrations. Idempotent.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'hash_key_version')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->unsignedSmallInteger('hash_key_version')->default(0)->after('hash_algorithm');
            });
        }

        if (! Schema::hasTable('audit_log_key_epochs')) {
            Schema::create('audit_log_key_epochs', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('key_version')->unique();
                $table->unsignedBigInteger('first_log_id');
                $table->string('signature', 64);
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_key_epochs');

        if (Schema::hasColumn('audit_logs', 'hash_key_version')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('hash_key_version');
            });
        }
    }
};
