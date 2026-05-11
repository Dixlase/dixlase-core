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

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restore records table
     * Stores audit trail for backup restore operations (who, what, when)
     */
    public function up(): void
    {
        Schema::create('restore_records', function (Blueprint $table) {
            $table->id();

            // Multisite scope. Site-scoped restores carry the site id;
            // network-wide restores leave it null.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // Which backup was restored from (retains history even after backup deletion)
            $table->unsignedBigInteger('backup_record_id')->nullable()->index();

            // Restore executor (retains history even after member deletion)
            $table->unsignedBigInteger('restored_by')->nullable()->index();
            $table->string('restored_by_name', 255)->nullable();   // Snapshot for display

            // Restore execution time
            $table->timestamp('restored_at')->index();

            // Actually restored targets (may restore only a portion of the entire backup)
            // e.g.: ["database"] / ["media", "custom"] / ["database", "media", "private", "custom"]
            $table->json('targets');

            // Status: pending, in_progress, completed, failed, rolled_back
            $table->string('status', 20)->default('pending')->index();

            // Safety snapshot before restore (used to undo restore)
            $table->unsignedBigInteger('pre_restore_backup_id')->nullable()->index();

            // Execution information
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error')->nullable();

            // Metadata: number of restored tables, files, warnings, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Composite index
            $table->index(['status', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restore_records');
    }
};
