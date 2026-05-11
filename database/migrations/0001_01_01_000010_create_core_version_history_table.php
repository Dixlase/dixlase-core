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
 * Audit trail of every core version transition (install / update / rollback).
 * Mirrors plugin_version_history for parity. Append-only: never updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_version_history', function (Blueprint $table) {
            $table->id();
            $table->string('old_version')->nullable(); // null on initial install
            $table->string('new_version');
            $table->string('old_signing_key_id')->nullable();
            $table->string('new_signing_key_id')->nullable();
            $table->string('old_author_id')->nullable();
            $table->string('new_author_id')->nullable();
            $table->unsignedInteger('files_changed_count')->default(0);
            $table->unsignedInteger('lines_added')->default(0);
            $table->unsignedInteger('lines_removed')->default(0);
            $table->boolean('signing_key_changed')->default(false)->index();
            $table->boolean('author_id_changed')->default(false)->index();
            $table->string('installation_method'); // install/update/rollback
            $table->string('installed_from_url')->nullable();
            $table->unsignedBigInteger('applied_by_id')->nullable()->index();
            $table->timestamp('applied_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_version_history');
    }
};
