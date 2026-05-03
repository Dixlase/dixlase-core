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
     * Extension source management table
     * Stores external source configurations for downloading plugins and themes
     */
    public function up(): void
    {
        Schema::create('extension_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 50)->index();
            $table->string('base_url', 500);
            $table->string('owner', 100)->nullable();
            $table->text('auth_token')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_official')->default(false);
            $table->text('official_signature')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->json('settings')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_sources');
    }
};
