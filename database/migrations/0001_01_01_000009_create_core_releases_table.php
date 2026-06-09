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
 * Tracks the current core release state. Single-row table (id = 1) seeded
 * during installation. Mirrors the per-row update fields on plugins/themes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_releases', function (Blueprint $table) {
            $table->id();

            // Source / discovery
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('source_repo')->nullable();

            // Update detection state
            $table->string('available_version')->nullable();
            $table->string('last_notified_version', 32)->nullable();
            $table->timestamp('available_version_published_at')->nullable();
            $table->string('release_url')->nullable();
            $table->text('release_notes')->nullable();
            $table->timestamp('last_version_check')->nullable();

            // Update failure tracking
            $table->timestamp('update_failed_at')->nullable();
            $table->text('update_failure_reason')->nullable();

            // Supply-chain / signing (mirrors plugins table)
            $table->string('signing_key_id')->nullable()->index();
            $table->string('author_id')->nullable()->index();
            $table->string('authority_key_id')->nullable();
            $table->string('installed_from_url')->nullable();
            $table->string('installation_method')->nullable();
            $table->timestamp('installed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_releases');
    }
};
