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
     * Records where audit-log retention cut the hash chain (#1048).
     *
     * `audit_log_retention_points`: the first chained row left after a
     * retention run and the previous_hash it carries, signed with the
     * integrity key (or the legacy seal key on a site without one).
     * Full-chain verification accepts that row in place of the genesis
     * row only when it matches the latest valid point, so a retention run
     * no longer reads as a broken chain while a deletion without one still
     * does.
     *
     * Added as a new migration because released versions have already run
     * the audit tables' create migrations. Idempotent.
     */
    public function up(): void
    {
        if (Schema::hasTable('audit_log_retention_points')) {
            return;
        }

        Schema::create('audit_log_retention_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('first_log_id');
            $table->string('previous_hash', 64);
            $table->timestamp('cutoff_at')->nullable();
            $table->unsignedBigInteger('deleted_rows')->default(0);
            $table->unsignedSmallInteger('key_version');
            $table->string('signature', 64);
            $table->timestamp('created_at')->nullable();

            $table->index('first_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_retention_points');
    }
};
