<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
     * Mark audit rows that a recorded personal-data erasure has redacted
     * (#478).
     *
     * Erasure clears `ip_address`, which the per-row hash covers, so a
     * redacted row can no longer be rehashed. `redacted_at` says the row was
     * changed on purpose, and `redaction_id` names the erasure that did it:
     * it equals the `request_id` of that erasure's own audit row, which
     * verification looks up before it accepts the row as redacted.
     *
     * Added as a new migration because released versions have already run
     * the table's create migration. Idempotent.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'redacted_at')) {
                $table->timestamp('redacted_at')->nullable();
            }

            if (! Schema::hasColumn('audit_logs', 'redaction_id')) {
                $table->string('redaction_id', 100)->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'redaction_id')) {
                $table->dropIndex(['redaction_id']);
                $table->dropColumn('redaction_id');
            }

            if (Schema::hasColumn('audit_logs', 'redacted_at')) {
                $table->dropColumn('redacted_at');
            }
        });
    }
};
